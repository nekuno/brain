<?php

namespace EventListener;

use Event\AccountConnectEvent;
use Service\AMQPManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AccountConnectSubscriber implements EventSubscriberInterface
{
    /**
     * @var AMQPManager
     */
    protected $amqpManager;

    public function __construct(AMQPManager $amqpManager)
    {
        $this->amqpManager = $amqpManager;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::ACCOUNT_CONNECTED => array('onAccountConnected'),
        );
    }

    public function onAccountConnected(AccountConnectEvent $event)
    {

        $message = array(
            'userId' => $event->getUserId(),
            'resourceOwner' => $event->getResourceOwner(),
        );

        $this->amqpManager->enqueueMessage($message, 'brain.fetching.links');
    }
}