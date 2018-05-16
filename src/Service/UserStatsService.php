<?php

namespace Service;

use Model\Content\ContentComparePaginatedManager;
use Model\Content\ContentPaginatedManager;
use Model\Group\GroupManager;
use Model\Relations\RelationsManager;
use Model\Shares\Shares;
use Model\Shares\SharesManager;
use Model\Stats\UserStats;
use Model\Stats\UserStatsCalculator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserStatsService
{
    /**
     * @var UserStatsCalculator
     */
    protected $userStatsCalculator;

    /**
     * @var RelationsManager
     */
    protected $relationsModel;

    /**
     * @var GroupManager
     */
    protected $groupModel;

    /**
     * @var ContentPaginatedManager
     */
    protected $contentPaginatedModel;

    /**
     * @var ContentComparePaginatedManager
     */
    protected $contentComparePaginatedModel;

    /**
     * @var SharesManager
     */
    protected $sharesManager;

    function __construct(
        UserStatsCalculator $userStatsManager,
        GroupManager $groupModel,
        RelationsManager $relationsModel,
        ContentPaginatedManager $contentPaginatedModel,
        ContentComparePaginatedManager $contentComparePaginatedModel,
        SharesManager $sharesManager
    ) {
        $this->userStatsCalculator = $userStatsManager;
        $this->groupModel = $groupModel;
        $this->relationsModel = $relationsModel;
        $this->contentPaginatedModel = $contentPaginatedModel;
        $this->contentComparePaginatedModel = $contentComparePaginatedModel;
        $this->sharesManager = $sharesManager;
    }

    public function getStats($userId)
    {
        $stats = $this->userStatsCalculator->calculateStats($userId);
        $this->completeStats($stats, $userId);

        return $stats;
    }

    protected function completeStats(UserStats $userStats, $userId)
    {
        $this->completeReceivedLikes($userStats, $userId);
        $this->completeUserLikes($userStats, $userId);
        $this->completeGroups($userStats, $userId);
        $this->completeContentLikes($userStats, $userId);
    }

    protected function completeReceivedLikes(UserStats $userStats, $userId)
    {
        $numberOfReceivedLikes = $this->relationsModel->countTo($userId, RelationsManager::LIKES);
        $userStats->setNumberOfReceivedLikes((integer)$numberOfReceivedLikes);
    }

    protected function completeUserLikes(UserStats $userStats, $userId)
    {
        $numberOfUserLikes = $this->relationsModel->countFrom($userId, RelationsManager::LIKES);
        $userStats->setNumberOfUserLikes((integer)$numberOfUserLikes);
    }

    protected function completeGroups(UserStats $userStats, $userId)
    {
        $groups = $this->groupModel->getAllByUserId($userId);
        $userStats->setGroupsBelonged($groups);
    }

    protected function completeContentLikes(UserStats $userStats, $userId)
    {
        $contentLikes = $this->contentPaginatedModel->countAll($userId);
        $userStats->setNumberOfContentLikes($contentLikes['Link']);
        $userStats->setNumberOfAudioLikes($contentLikes['Audio']);
        $userStats->setNumberOfImageLikes($contentLikes['Image']);
        $userStats->setNumberOfVideoLikes($contentLikes['Video']);
    }

    public function getComparedStats($userId, $otherUserId)
    {
        if (null === $otherUserId) {
            throw new NotFoundHttpException('User not found');
        }

        if ($userId === $otherUserId) {
            throw new \InvalidArgumentException('Cannot get compared stats between an user and themselves');
        }

        return $this->userStatsCalculator->calculateComparedStats($userId, $otherUserId);
    }

    public function updateShares($userId1, $userId2)
    {
        $topLinks = $this->userStatsCalculator->calculateTopLinks($userId1, $userId2);
        $filters = array(
            'id' => (integer)$userId1,
            'id2' => (integer)$userId2,
            'showOnlyCommon' => true
        );
        $sharedLinksAmount = $this->contentComparePaginatedModel->countTotal($filters);

        $shares = new Shares();
        $shares->setTopLinks($topLinks);
        $shares->setSharedLinks($sharedLinksAmount);

        return $this->sharesManager->merge($userId1, $userId2, $shares);
    }
}