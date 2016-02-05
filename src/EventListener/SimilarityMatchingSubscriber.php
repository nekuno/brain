<?php

namespace EventListener;

use Event\MatchingEvent;
use Event\SimilarityEvent;
use Model\User\GroupModel;
use Service\EmailNotifications;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SimilarityMatchingSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailNotifications
     */
    protected $emailNotifications;

    /**
     * @var GroupModel
     */
    protected $groupModel;

    public function __construct(EmailNotifications $emailNotifications, GroupModel $groupModel)
    {
        $this->emailNotifications = $emailNotifications;
        $this->groupModel = $groupModel;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::MATCHING_UPDATED => array('onMatchingUpdated'),
            \AppEvents::SIMILARITY_UPDATED => array('onSimilarityUpdated'),
        );
    }

    public function onMatchingUpdated(MatchingEvent $event)
    {
        echo sprintf('Matching updated between %d - %d, with value %s' . "\n", $event->getUser1(), $event->getUser2(), $event->getMatching());
    }

    public function onSimilarityUpdated(SimilarityEvent $event)
    {
        echo sprintf('Similarity updated between %d -  %d, with value %s' . "\n", $event->getUser1(), $event->getUser2(), $event->getSimilarity());
    }
}