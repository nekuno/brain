<?php

namespace Service;

use Model\Matching\MatchingManager;
use Model\Photo\PhotoManager;
use Model\Profile\OtherProfileData;
use Model\Profile\ProfileManager;
use Model\Similarity\SimilarityManager;
use Model\User\User;
use Model\User\UserManager;

class ProfileService
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var ProfileManager
     */
    protected $profileManager;

    /**
     * @var MatchingManager
     */
    protected $matchingManager;

    /**
     * @var SimilarityManager
     */
    protected $similarityManager;

    /**
     * @var PhotoManager
     */
    protected $photoManager;


    public function getOtherPage($slug, User $ownUser)
    {
        $ownUserId = $ownUser->getId();

        $otherUser = $this->userManager->getBySlug($slug);
        $otherUserId = $otherUser->getId();

        $otherProfileData = new OtherProfileData();
        $otherProfileData->setUserName($otherUser->getUsername());

        $profile = $this->profileManager->getById($otherUserId);
        $otherProfileData->setLocation($profile->get('location'));
        $otherProfileData->setBirthday($profile->get('birthday'));

        $matching = $this->matchingManager->getMatchingBetweenTwoUsersBasedOnAnswers($otherUserId, $ownUserId);
        $otherProfileData->setMatching($matching->getMatching());

        $similarity = $this->similarityManager->getCurrentSimilarity($otherUserId, $ownUserId);
        $otherProfileData->setSimilarity($similarity->getSimilarity());

        $photos = $this->photoManager->getAll($otherUserId);
        $otherProfileData->setPhotos($photos);

        return $otherUser;
    }
}