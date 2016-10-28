<?php

namespace EventListener;

use Event\MatchingEvent;
use Event\SimilarityEvent;
use Model\Entity\EmailNotification;
use Model\User\Filters\FilterUsers;
use Model\User\Group\GroupModel;
use Model\User\ProfileModel;
use Manager\UserManager;
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
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var ProfileModel
     */
    protected $profileModel;

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

    /**
     * @var string
     */
    protected $socialHost;

    public function __construct(EmailNotifications $emailNotifications, UserManager $userManager, ProfileModel $profileModel, GroupModel $groupModel, Translator $translator, NotificationManager $notificationManager, $socialHost)
    {
        $this->emailNotifications = $emailNotifications;
        $this->userManager = $userManager;
        $this->profileModel = $profileModel;
        $this->groupModel = $groupModel;
        $this->translator = $translator;
        $this->notificationManager = $notificationManager;
        $this->socialHost = $socialHost;
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
        $this->handleGroups($groupsFollowers['direct'], $event->getUser1(), $event->getUser2(), 'compatibility', $event->getMatching());

        // Groups in which 2 is follower and 1 is influencer (group creator)
        $this->handleGroups($groupsFollowers['inverse'], $event->getUser2(), $event->getUser1(), 'compatibility', $event->getMatching());
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

        // Filter is stored in base 100, value is stored in base 1
        $value = $value * 100;

        foreach ($groups as $groupId) {
            $group = $this->groupModel->getById($groupId);
            if ($filterUsers = $group->getFilterUsers()) {
                /* @var $filterUsers FilterUsers */
                $userFilters = $filterUsers->getUserFilters();
                if (isset($userFilters[$filter]) && $userFilters[$filter] <= $value && !$this->notificationManager->areNotified($follower, $influencer)) {
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

        $user = $this->userManager->getById($follower);
        $username = $user->getUsername();
        $email = $user->getEmail();

        $profile = $this->profileModel->getById($follower);
        if (isset($profile['interfaceLanguage'])) {
            $this->translator->setLocale($profile['interfaceLanguage']);
        }

        $userOther = $this->userManager->getById($influencer);
        $usernameOther = $userOther->getUsername();
        $emailOther = $userOther->getEmail();
        $urlOther = $this->socialHost . 'profile/show/' . $influencer;

        $info = array(
            'follower' => $follower,
            'influencer' => $influencer,
            'filter' => $filter,
            'value' => $value,
            'username' => $username,
            'email' => $email,
            'usernameOther' => $usernameOther,
            'emailOther' => $emailOther,
            'urlOther' => $urlOther,
        );

        $recipients = $this->emailNotifications->send(
            EmailNotification::create()
                ->setType(EmailNotification::INFLUENCER_FOUND)
                ->setSubject($this->translator->trans('notifications.messages.influencer_found.subject', $info))
                ->setUserId($follower)
                ->setRecipient($email)
                ->setInfo($info)
        );

        return $recipients;
    }

    protected function sendInfluencerMail($influencer, $follower, $filter, $value)
    {

        $user = $this->userManager->getById($influencer);
        $username = $user->getUsername();
        $email = $user->getEmail();

        $profile = $this->profileModel->getById($influencer);
        if (isset($profile['interfaceLanguage'])) {
            $this->translator->setLocale($profile['interfaceLanguage']);
        }

        $userOther = $this->userManager->getById($follower);
        $usernameOther = $userOther->getUsername();
        $emailOther = $userOther->getEmail();
        $urlOther = $this->socialHost . 'profile/show/' . $follower;

        $info = array(
            'follower' => $follower,
            'influencer' => $influencer,
            'filter' => $filter,
            'value' => $value,
            'username' => $username,
            'email' => $email,
            'usernameOther' => $usernameOther,
            'emailOther' => $emailOther,
            'urlOther' => $urlOther,
        );

        $recipients = $this->emailNotifications->send(
            EmailNotification::create()
                ->setType(EmailNotification::FOLLOWER_FOUND)
                ->setSubject($this->translator->trans('notifications.messages.follower_found.subject', $info))
                ->setUserId($influencer)
                ->setRecipient($email)
                ->setInfo($info)
        );

        return $recipients;
    }

}