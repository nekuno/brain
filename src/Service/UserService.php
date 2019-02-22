<?php

namespace Service;

use Model\Availability\AvailabilityManager;
use Model\Photo\GalleryManager;
use Model\Photo\PhotoManager;
use Model\Proposal\ProposalManager;
use Model\Question\UserAnswerPaginatedManager;
use Model\User\User;
use Model\User\UserManager;
use Model\Profile\ProfileManager;
use Model\Rate\RateManager;
use Model\Token\TokensManager;
use Model\Token\TokenStatus\TokenStatusManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserService
{
    protected $userManager;
    protected $profileManager;
    protected $tokensModel;
    protected $tokenStatusManager;
    protected $rateModel;
    protected $linkService;
    protected $authService;
    protected $instantConnection;
    protected $photoManager;
    protected $galleryManager;
    protected $proposalManager;
    protected $availabilityManager;
    protected $userAnswerPaginatedManager;

    /**
     * UserService constructor.
     * @param UserManager $userManager
     * @param ProfileManager $profileManager
     * @param TokensManager $tokensModel
     * @param TokenStatusManager $tokenStatusManager
     * @param RateManager $rateModel
     * @param LinkService $linkService
     * @param AuthService $authService
     * @param InstantConnection $instantConnection
     * @param PhotoManager $photoManager
     * @param GalleryManager $galleryManager
     * @param ProposalManager $proposalManager
     * @param UserAnswerPaginatedManager $userAnswerPaginatedManager
     */
    public function __construct(
        UserManager $userManager,
        ProfileManager $profileManager,
        TokensManager $tokensModel,
        TokenStatusManager $tokenStatusManager,
        RateManager $rateModel,
        LinkService $linkService,
        AuthService $authService,
        InstantConnection $instantConnection,
        PhotoManager $photoManager,
        GalleryManager $galleryManager,
        ProposalManager $proposalManager,
        AvailabilityManager $availabilityManager,
        UserAnswerPaginatedManager $userAnswerPaginatedManager
    ) {
        $this->userManager = $userManager;
        $this->profileManager = $profileManager;
        $this->tokensModel = $tokensModel;
        $this->tokenStatusManager = $tokenStatusManager;
        $this->rateModel = $rateModel;
        $this->linkService = $linkService;
        $this->authService = $authService;
        $this->instantConnection = $instantConnection;
        $this->userAnswerPaginatedManager = $userAnswerPaginatedManager;
        //TODO: Move to PhotoService and remove USerManager->PhotoManager dependencies
        $this->photoManager = $photoManager;
        $this->galleryManager = $galleryManager;
        $this->proposalManager = $proposalManager;
        $this->availabilityManager = $availabilityManager;
    }

    public function createUser(array $userData, array $profileData)
    {
        //TODO: Extract createUserPhoto to here
        $user = $this->userManager->create($userData);
        $this->profileManager->create($user->getId(), $profileData);

        return $user;
    }

    public function updateUser(array $userData)
    {
        $userData = $this->updateEnabled($userData);
        $user = $this->userManager->update($userData);

        return $user;
    }

    protected function updateEnabled(array $userData)
    {
        $userId = $userData['userId'];
        $user = $this->userManager->getById($userId);

        if ($user->isEnabled() !== $userData['enabled']) {
            $fromAdmin = true;
            $this->userManager->setEnabled($userId, $userData['enabled'], $fromAdmin);
        }

        unset($userData['enabled']);

        return $userData;
    }

    public function deleteUser($userId)
    {
        $messagesData = array('userId' => $userId);
        $this->instantConnection->deleteMessages($messagesData);

        $user = $this->userManager->getById($userId);
        $photoId = $user->getPhoto()->getId();
        if ($photoId) {
            $this->photoManager->remove($photoId);
        }

        $this->galleryManager->deleteAllFromUser($user);

        $this->tokenStatusManager->removeAll($userId);
        $this->tokensModel->removeAll($userId);

        $deletedLikesUrls = $this->rateModel->deleteAllLinksByUser($userId);
        $this->linkService->deleteNotLiked($deletedLikesUrls);

        $this->proposalManager->deleteByUser($user);

        $this->profileManager->remove($userId);

        $this->userManager->delete($userId);

        return $user;
    }

    //TODO: Move to AuthService?
    public function getOwnUser($jwt, $locale)
    {
        $user = $this->authService->getUser($jwt);

        $data = $this->buildOwnUser($user, $locale);
        $data['jwt'] = $jwt;

        return $data;
    }

    //TODO: Do we want locale be fetched from profile?
    public function buildOwnUser(User $user, $locale)
    {
        $profile = $this->profileManager->getById($user->getId());
        $questionsFilters = array('id' => $user->getId(), 'locale' => $locale);
        $countQuestions = $this->userAnswerPaginatedManager->countTotal($questionsFilters);

        $availability = $this->availabilityManager->getByUser($user);
        $user->setAvailability($availability);

        return ['user' => $user, 'profile' => $profile, 'questionsTotal' => $countQuestions];
    }

    public function getOneUser($userId)
    {
        $user = $this->userManager->getById($userId);

        return $user;
    }

    public function getOther($slug)
    {
        try {
            $user = $this->userManager->getBySlug($slug);
        } catch (NotFoundHttpException $e) {
            return null;
        }

        $locale = $this->profileManager->getInterfaceLocale($user->getId());
        $proposals = $this->proposalManager->getByUser($user, $locale);
        $user->setProposals($proposals);

        $userArray = $user->jsonSerialize();
        $userArray = $this->userManager->deleteOtherUserFields($userArray);

        return $userArray;
    }

    public function getOtherPublic($slug)
    {
        $user = $this->userManager->getPublicBySlug($slug);

        $locale = $this->profileManager->getInterfaceLocale($user->getId());
        $proposals = $this->proposalManager->getByUser($user, $locale);
        $user->setProposals($proposals);

        $userArray = $user->jsonSerialize();
        $userArray = $this->userManager->deleteOtherUserFields($userArray);

        return $userArray;
    }

    public function getOtherByProposal($proposal)
    {
        $candidateSlugs = $this->userManager->getByProposal($proposal);

        $matches = array();
        foreach ($candidateSlugs as $candidateSlug) {
            $matches[] = $this->getOtherPublic($candidateSlug);
        }

        return $matches;
    }

}