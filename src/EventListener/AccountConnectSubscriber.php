<?php

namespace EventListener;

use ApiConsumer\Event\OAuthTokenEvent;
use ApiConsumer\Factory\ResourceOwnerFactory;
use ApiConsumer\ResourceOwner\LinkedinResourceOwner;
use Event\AccountConnectEvent;
use Event\ProcessLinksEvent;
use Model\User\UserManager;
use Model\GhostUser\GhostUserManager;
use Model\Profile\ProfileManager;
use Model\SocialNetwork\SocialProfileManager;
use Model\Token\Token;
use Model\Token\TokenManager;
use Service\AMQPManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Model\SocialNetwork\SocialProfile;
use ApiConsumer\ResourceOwner\FacebookResourceOwner;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;

class AccountConnectSubscriber implements EventSubscriberInterface
{
    /**
     * @var AMQPManager
     */
    protected $amqpManager;

    /**
     * @var UserManager
     */
    protected $um;

    /**
     * @var GhostUserManager
     */
    protected $gum;

    /**
     * @var SocialProfileManager
     */
    protected $spm;

    /**
     * @var ResourceOwnerFactory
     */
    protected $resourceOwnerFactory;

    /**
     * @var TokenManager
     */
    protected $tokensModel;

    /**
     * @var ProfileManager
     */
    protected $profileModel;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(AMQPManager $amqpManager, UserManager $um, GhostUserManager $gum, SocialProfileManager $spm, ResourceOwnerFactory $resourceOwnerFactory, TokenManager $tokensModel, ProfileManager $pm, EventDispatcherInterface $dispatcher)
    {
        $this->amqpManager = $amqpManager;
        $this->um = $um;
        $this->gum = $gum;
        $this->spm = $spm;
        $this->resourceOwnerFactory = $resourceOwnerFactory;
        $this->tokensModel = $tokensModel;
        $this->profileModel = $pm;
        $this->dispatcher = $dispatcher;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::ACCOUNT_CONNECTED => array('onAccountConnected'),
            \AppEvents::TOKEN_PRE_SAVE => array('onTokenSave'),
        );
    }

    public function onAccountConnected(AccountConnectEvent $event)
    {
        $userId = $event->getUserId();
        $token = $event->getToken();
        $resourceOwner = $token->getResourceOwner();

        switch ($resourceOwner) {
            case TokenManager::TWITTER:
                $this->createTwitterSocialProfile($token, $userId);
                break;
            case TokenManager::LINKEDIN:
                $this->completeProfileWithLinkedin($token, $userId);
                $this->dispatcher->dispatch(\AppEvents::PROCESS_FINISH, new ProcessLinksEvent($userId, $resourceOwner, array()));
                break;
            default:
                break;
        }

        $message = array(
            'userId' => $userId,
            'resourceOwner' => $resourceOwner,
        );

        $this->amqpManager->enqueueFetching($message);
    }

    public function onTokenSave(OAuthTokenEvent $event)
    {
        $token = $event->getToken();
        $resourceOwner = $token->getResourceOwner();

        switch ($resourceOwner) {
            case TokenManager::FACEBOOK:
                $this->extendFacebook($token);
                break;
            case TokenManager::LINKEDIN:
                $this->extendLinkedin($token);
                break;
            default:
                break;
        }
    }

    private function extendFacebook(Token $token, $attempts = 0)
    {
        /* @var $facebookResourceOwner FacebookResourceOwner */
        $facebookResourceOwner = $this->resourceOwnerFactory->build(TokenManager::FACEBOOK);

        try {
            $facebookResourceOwner->extend($token);
        } catch (\Exception $e) {
            if ($attempts < 5) {
                $this->extendFacebook($token, ++$attempts);
            }
        }
    }

    private function extendLinkedin(Token $token, $attempts = 0)
    {
        /* @var $linkedinResourceOwner LinkedinResourceOwner */
        $linkedinResourceOwner = $this->resourceOwnerFactory->build(TokenManager::LINKEDIN);

        try {
            $linkedinResourceOwner->forceRefreshAccessToken($token);
        } catch (\Exception $e) {
            if ($attempts < 5) {
                $this->extendLinkedin($token, ++$attempts);
            }
        }
    }

    private function createTwitterSocialProfile(Token $token, $userId)
    {
        $resourceOwner = TokenManager::TWITTER;
        /** @var TwitterResourceOwner $resourceOwnerObject */
        $resourceOwnerObject = $this->resourceOwnerFactory->build($resourceOwner);
        $profileUrl = $resourceOwnerObject->requestProfileUrl($token);
        if ($profileUrl) {
            $profile = new SocialProfile($userId, $profileUrl, $resourceOwner);

            if ($ghostUser = $this->gum->getBySocialProfile($profile)) {
                $this->um->fuseUsers($userId, $ghostUser->getId());
                $this->gum->saveAsUser($userId);
            } else {
                $this->spm->addSocialProfile($profile);
            }
        }
    }

    private function completeProfileWithLinkedin(Token $token, $userId)
    {
        $resourceOwner = TokenManager::LINKEDIN;
        /** @var LinkedinResourceOwner $resourceOwnerObject */
        $resourceOwnerObject = $this->resourceOwnerFactory->build($resourceOwner);
        $accessToken = array(
            'access_token' => $token->getOauthToken(),
            'oauth_token_secret' => $token->getOauthTokenSecret(),
        );
        $userInformation = $resourceOwnerObject->getUserInformation($accessToken);
        $response = $userInformation->getData();
        if (isset($response['industry'])) {
            $profile = $this->profileModel->getById($userId);
            if (!$profile->get('industry')) {
                try {
                    $industry = $this->profileModel->getIndustryIdFromDescription($response['industry']);
                    $profile->set('industry', $industry);
                    //TODO: Change this to setProfileOption?
                    $this->profileModel->update($userId, $profile->toArray());
                } catch (\Exception $e) {}
            }
        }
    }
}