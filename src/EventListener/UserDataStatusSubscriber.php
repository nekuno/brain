<?php

namespace EventListener;

use Event\FetchEvent;
use Event\MatchingExpiredEvent;
use Event\ProcessLinksEvent;
use Event\ContentRatedEvent;
use Model\Token\TokenStatus\TokenStatusManager;
use Service\AMQPManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Worker\MatchingCalculatorWorker;

class UserDataStatusSubscriber implements EventSubscriberInterface
{
    protected $tokenStatusManager;
    protected $amqpManager;

    /**
     * @param TokenStatusManager $tokenStatusManager
     * @param AMQPManager $amqpManager
     */
    public function __construct(TokenStatusManager $tokenStatusManager, AMQPManager $amqpManager)
    {
        $this->tokenStatusManager = $tokenStatusManager;
        $this->amqpManager = $amqpManager;
    }

    /**
     * { @inheritdoc }
     */
    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::FETCH_START => array('onFetchStart'),
            \AppEvents::FETCH_FINISH => array('onFetchFinish'),
            \AppEvents::PROCESS_START => array('onProcessStart'),
            \AppEvents::PROCESS_FINISH => array('onProcessFinish'),
            \AppEvents::MATCHING_EXPIRED => array('onMatchingExpired'),
            \AppEvents::CONTENT_RATED => array('onContentRated'),
        );
    }

    public function onFetchStart(FetchEvent $event)
    {
        $userId = $event->getUser();
        $resourceOwner = $event->getResourceOwner();

        $this->tokenStatusManager->setFetched($userId, $resourceOwner, 0);
    }

    public function onFetchFinish(FetchEvent $event)
    {
        $userId = $event->getUser();
        $resourceOwner = $event->getResourceOwner();

        $this->tokenStatusManager->setFetched($userId, $resourceOwner, 1);
    }

    public function onProcessStart(ProcessLinksEvent $event)
    {
        $userId = $event->getUser();
        $resourceOwner = $event->getResourceOwner();

        $this->tokenStatusManager->setProcessed($userId, $resourceOwner, 0);
    }

    public function onProcessFinish(ProcessLinksEvent $event)
    {
        $userId = $event->getUser();
        $resourceOwner = $event->getResourceOwner();

        $this->tokenStatusManager->setProcessed($userId, $resourceOwner, 1);

        $data = array(
            'userId' => $userId,
            'resourceOwner' => $resourceOwner,
        );

        $this->amqpManager->enqueueMatching($data, MatchingCalculatorWorker::TRIGGER_PROCESS_FINISHED);
    }

    public function onMatchingExpired(MatchingExpiredEvent $event)
    {

        $data = array(
            'user_1_id' => $event->getUser1(),
            'user_2_id' => $event->getUser2(),
            'matching_type' => $event->getType(),
        );

        $this->amqpManager->enqueueMatching($data, MatchingCalculatorWorker::TRIGGER_MATCHING_EXPIRED);

    }

    public function onContentRated(ContentRatedEvent $event)
    {

        $data = array(
            'userId' => $event->getUser(),
        );

        $this->amqpManager->enqueueMatching($data, MatchingCalculatorWorker::TRIGGER_CONTENT_RATED);
    }

}
