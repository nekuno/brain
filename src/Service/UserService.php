<?php

namespace Service;

use Model\Neo4j\Neo4jException;
use Model\Photo\GalleryManager;
use Model\Photo\PhotoManager;
use Model\Proposal\ProposalManager;
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
    protected $instantConnection;
    protected $photoManager;
    protected $galleryManager;
    protected $proposalManager;

    /**
     * UserService constructor.
     * @param UserManager $userManager
     * @param ProfileManager $profileManager
     * @param TokensManager $tokensModel
     * @param TokenStatusManager $tokenStatusManager
     * @param RateManager $rateModel
     * @param LinkService $linkService
     * @param InstantConnection $instantConnection
     * @param PhotoManager $photoManager
     * @param GalleryManager $galleryManager
     * @param ProposalManager $proposalManager
     */
    public function __construct(
        UserManager $userManager,
        ProfileManager $profileManager,
        TokensManager $tokensModel,
        TokenStatusManager $tokenStatusManager,
        RateManager $rateModel,
        LinkService $linkService,
        InstantConnection $instantConnection,
        PhotoManager $photoManager,
        GalleryManager $galleryManager,
        ProposalManager $proposalManager
    ) {
        $this->userManager = $userManager;
        $this->profileManager = $profileManager;
        $this->tokensModel = $tokensModel;
        $this->tokenStatusManager = $tokenStatusManager;
        $this->rateModel = $rateModel;
        $this->linkService = $linkService;
        $this->instantConnection = $instantConnection;
        //TODO: Move to PhotoService and remove USerManager->PhotoManager dependencies
        $this->photoManager = $photoManager;
        $this->galleryManager = $galleryManager;
        $this->proposalManager = $proposalManager;
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

}