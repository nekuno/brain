<?php

namespace EventListener;

use Event\MatchingEvent;
use Event\SimilarityEvent;
use Model\Entity\EmailNotification;
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

        // Groups in which 1 is follower and 2 is influencer (group creator)
        $this->handleGroups($groupsFollowers['direct'], $event->getUser1(), $event->getUser2(), 'matching', $event->getMatching());

        // Groups in which 2 is follower and 1 is influencer (group creator)
        $this->handleGroups($groupsFollowers['inverse'], $event->getUser2(), $event->getUser1(), 'matching', $event->getMatching());
    }

    public function onSimilarityUpdated(SimilarityEvent $event)
    {
        echo sprintf('Similarity updated between %d -  %d, with value %s' . "\n", $event->getUser1(), $event->getUser2(), $event->getSimilarity());
        $groupsFollowers = $this->groupModel->getIsGroupFollowersOf($event->getUser1(), $event->getUser2());

        // Groups in which 1 is follower and 2 is influencer (group creator)
        $this->handleGroups($groupsFollowers['direct'], $event->getUser1(), $event->getUser2(), 'similarity', $event->getSimilarity());

        // Groups in which 2 is follower and 1 is influencer (group creator)
        $this->handleGroups($groupsFollowers['inverse'], $event->getUser2(), $event->getUser1(), 'similarity', $event->getSimilarity());
    }

    protected function handleGroups(array $groups, $follower, $influencer, $filter, $value)
    {
        foreach ($groups as $groupId) {
            $group = $this->groupModel->getById($groupId);
            if (isset($group['filterUsers']['userFilters'][$filter])) {
                if ($group['filterUsers']['userFilters'][$filter] <= $value && !$this->notificationManager->areNotified($follower, $influencer)) {
                    // Send mails
                    $this->sendFollowerMail($follower, $influencer, $filter, $value);
                    $this->sendInfluencerMail($influencer, $follower, $filter, $value);
                    // Save notified
                    $this->notificationManager->notify($follower, $influencer);
                }
            }
        }
    }

    protected function sendFollowerMail($follower, $influencer, $filter, $value)
    {

        //TODO: Extract from user
        $email = 'juanlu@comakai.com';

        $recipients = $this->emailNotifications->send(
            EmailNotification::create()
                ->setType(EmailNotification::INFLUENCER_FOUND)
                ->setSubject($this->translator->trans('notifications.messages.influencer_found.subject'))
                ->setUserId($follower)
                ->setRecipient($email)
                ->setInfo(
                    array(
                        'follower' => $follower,
                        'influencer' => $influencer,
                        'filter' => $filter,
                        'value' => $value,
                    )
                )
        );

        return $recipients;
    }

    protected function sendInfluencerMail($influencer, $follower, $filter, $value)
    {

        //TODO: Extract from user
        $email = 'juanlu@comakai.com';

        $recipients = $this->emailNotifications->send(
            EmailNotification::create()
                ->setType(EmailNotification::FOLLOWER_FOUND)
                ->setSubject($this->translator->trans('notifications.messages.follower_found.subject'))
                ->setUserId($influencer)
                ->setRecipient($email)
                ->setInfo(
                    array(
                        'follower' => $follower,
                        'influencer' => $influencer,
                        'filter' => $filter,
                        'value' => $value,
                    )
                )
        );

        return $recipients;
    }

}