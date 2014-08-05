<?php
namespace ApiConsumer\Listener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ApiConsumer\Event\FilterTokenEvent;
use ApiConsumer\Auth\UserProviderInterface;

class TokenRefreshedSubscriber implements EventSubscriberInterface
{
	/**
	* @var UserProviderInterface
	*/
	protected $userProvider;

    public function __construct(UserProviderInterface $userProvider)
    {
    	$this->userProvider = $userProvider;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'token.refreshed'  => array('onTokenRefreshed', 0),
        );
    }

    public function onTokenRefreshed(FilterTokenEvent $event)
    {
		$user = $event->getUser();
    	$this->userProvider->updateAccessToken($user['resourceOwner'], $user['user_id'], $user['oauthToken'], $user['createdTime'], $user['expireTime']);
    }
}