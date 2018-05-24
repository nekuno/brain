<?php

namespace EventListener;

use Event\UserStatusChangedEvent;
use GuzzleHttp\Exception\RequestException;
use Service\InstantConnection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserStatusSubscriber implements EventSubscriberInterface
{
    /**
     * @var InstantConnection
     */
    protected $instantConnection;

    public function __construct(InstantConnection $instantConnection)
    {
        $this->instantConnection = $instantConnection;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::USER_STATUS_CHANGED => array('onUserStatusChanged'),
        );
    }

    public function onUserStatusChanged(UserStatusChangedEvent $event)
    {
        $data = array('userId' => $event->getUserId(), 'status' => $event->getStatus());
        try {
            $this->instantConnection->setStatus($data);
        } catch (RequestException $e) {
        }
    }
}