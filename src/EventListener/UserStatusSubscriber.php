<?php

namespace EventListener;

use Event\UserStatusChangedEvent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserStatusSubscriber implements EventSubscriberInterface
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::USER_STATUS_CHANGED => array('onUserStatusChanged'),
        );
    }

    public function onUserStatusChanged(UserStatusChangedEvent $event)
    {
        $json = array('userId' => $event->getUserId(), 'status' => $event->getStatus());
        try {
            $this->client->post('api/user/status', array('json' => $json));
        } catch (RequestException $e) {

        }
    }
}