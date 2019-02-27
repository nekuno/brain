<?php

namespace Service;

use Model\Recommendation\UserRecommendation;
use Model\Recommendation\UserRecommendationBuilder;
use Model\Relations\RelationsManager;
use Model\Shares\SharesManager;
use Model\User\User;
use Model\User\UserManager;

class RelationsService
{
    protected $relationsManager;

    protected $userManager;

    protected $userRecommendationBuilder;

    protected $sharesManager;

    /**
     * RelationsService constructor.
     * @param RelationsManager $relationsManager
     * @param UserManager $userManager
     * @param UserRecommendationBuilder $userRecommendationBuilder
     * @param SharesManager $sharesManager
     */
    public function __construct(RelationsManager $relationsManager, UserManager $userManager, UserRecommendationBuilder $userRecommendationBuilder, SharesManager $sharesManager)
    {
        $this->relationsManager = $relationsManager;
        $this->userManager = $userManager;
        $this->userRecommendationBuilder = $userRecommendationBuilder;
        $this->sharesManager = $sharesManager;
    }

    /**
     * @param User $user
     * @return UserRecommendation[]
     * @throws \Exception
     */
    public function getFriends(User $user)
    {
        $friendsIds = $this->relationsManager->getFriendsIds($user);

        $userId = $user->getId();
        $friends = array();
        foreach ($friendsIds AS $friendId)
        {
            $friendResult = $this->userManager->getAsRecommendation($friendId, $userId);
            $friendsArray = $this->userRecommendationBuilder->buildUserRecommendations($friendResult);
            $friend = reset($friendsArray);

            $shares = $this->sharesManager->get($friendId, $userId);
            $sharedLinks = null == $shares ? 0 : $shares->getSharedLinks();
            $friend->setSharedLinks($sharedLinks);

            $friends[] = reset($friendsArray);
        }

        return $friends;
    }

}