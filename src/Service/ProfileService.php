<?php

namespace Service;

use Model\Matching\MatchingManager;
use Model\Photo\PhotoManager;
use Model\Profile\NaturalProfileBuilder;
use Model\Profile\OtherProfileData;
use Model\Profile\ProfileManager;
use Model\Rate\RateManager;
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
     * @var NaturalProfileBuilder
     */
    protected $naturalProfileBuilder;

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

    /**
     * @var RateManager
     */
    protected $rateManager;

    /**
     * @var MetadataService
     */
    protected $metadataService;

    /**
     * ProfileService constructor.
     * @param UserManager $userManager
     * @param ProfileManager $profileManager
     * @param NaturalProfileBuilder $naturalProfileBuilder
     * @param MatchingManager $matchingManager
     * @param SimilarityManager $similarityManager
     * @param PhotoManager $photoManager
     * @param RateManager $rateManager
     * @param MetadataService $metadataService
     */
    public function __construct(UserManager $userManager, ProfileManager $profileManager, NaturalProfileBuilder $naturalProfileBuilder, MatchingManager $matchingManager, SimilarityManager $similarityManager, PhotoManager $photoManager, RateManager $rateManager, MetadataService $metadataService)
    {
        $this->userManager = $userManager;
        $this->profileManager = $profileManager;
        $this->naturalProfileBuilder = $naturalProfileBuilder;
        $this->matchingManager = $matchingManager;
        $this->similarityManager = $similarityManager;
        $this->photoManager = $photoManager;
        $this->rateManager = $rateManager;
        $this->metadataService = $metadataService;
    }

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

        $locale = $profile->get('interfaceLanguage');
        $profileMetadata = $this->metadataService->getProfileMetadataWithChoices($locale);
        $this->naturalProfileBuilder->setMetadata($profileMetadata);

        $liked = $this->rateManager->isLiked($ownUserId, $otherUserId);
        $otherProfileData->setLike($liked);

        $naturalProfile = $this->naturalProfileBuilder->buildNaturalProfile($profile);
        $otherProfileData->setNaturalProfile($naturalProfile);

        return $otherProfileData;
    }
}