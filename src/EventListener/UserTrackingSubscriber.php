<?php

namespace EventListener;

use Event\UserRegisteredEvent;
use Model\User\UserTrackingManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserTrackingSubscriber implements EventSubscriberInterface
{


    protected $userTrackingModel;

    public function __construct(UserTrackingManager $userTrackingModel)
    {
        $this->userTrackingModel = $userTrackingModel;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::USER_REGISTERED => array('onUserRegistered'),
        );
    }

    public function onUserRegistered(UserRegisteredEvent $event)
    {
        $user = $event->getUser();
        $trackingData = $event->getTrackingData();
        $this->userTrackingModel->set($user->getId(), 'Registration success', 'Registration', null, $trackingData);
    }
}