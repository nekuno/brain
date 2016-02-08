<?php

namespace EventListener;

use Event\MatchingEvent;
use Event\SimilarityEvent;
use Model\User\GroupModel;
use Service\EmailNotifications;
use Service\NotificationManager;
use Silex\Translator;
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

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var NotificationManager
     */
    protected $notificationManager;

    public function __construct(EmailNotifications $emailNotifications, GroupModel $groupModel, Translator $translator, NotificationManager $notificationManager)
    {
        $this->emailNotifications = $emailNotifications;
        $this->groupModel = $groupModel;
        $this->translator = $translator;
        $this->notificationManager = $notificationManager;
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
        $groupsFollowers = $this->groupModel->getIsGroupFollowersOf($event->getUser1(), $event->getUser2());
        foreach ($groupsFollowers['direct'] as $groupId) {
            //Groups in which 1 is follower of 2

        }
        foreach ($groupsFollowers['inverse'] as $groupId) {
            //Groups in which 2 is follower of 1
        }
    }

    public function onSimilarityUpdated(SimilarityEvent $event)
    {
        echo sprintf('Similarity updated between %d -  %d, with value %s' . "\n", $event->getUser1(), $event->getUser2(), $event->getSimilarity());
        $groupsFollowers = $this->groupModel->getIsGroupFollowersOf($event->getUser1(), $event->getUser2());
        foreach ($groupsFollowers['direct'] as $groupId) {
            //Groups in which 1 is follower of 2

        }
        foreach ($groupsFollowers['inverse'] as $groupId) {
            //Groups in which 2 is follower of 1
        }
    }
}