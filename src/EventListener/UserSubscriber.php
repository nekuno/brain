<?php

namespace EventListener;

use Event\UserEvent;
use Model\User\Thread\ThreadManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserSubscriber implements EventSubscriberInterface
{
    /**
     * @var ThreadManager
     */
    protected $threadManager;

    public function __construct(ThreadManager $threadManager)
    {
        $this->threadManager = $threadManager;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::USER_CREATED => array('onUserCreated'),
        );
    }

    public function onUserCreated(UserEvent $event)
    {
        $user = $event->getUser();
        $threads = $this->threadManager->getDefaultThreads();
        foreach ($threads as $threadProperties) {
            $this->threadManager->create($user['qnoow_id'], $threadProperties);
            // TODO: Enqueue thread recomendation
        }
    }
}