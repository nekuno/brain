<?php

namespace EventListener;

use Event\GroupEvent;
use Event\ProfileEvent;
use Event\UserEvent;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Model\User\Thread\ThreadManager;
use Service\ChatMessageNotifications;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserSubscriber implements EventSubscriberInterface
{
    /**
     * @var ThreadManager
     */
    protected $threadManager;

    protected $chat;

    public function __construct(ThreadManager $threadManager, ChatMessageNotifications $chat)
    {
        $this->threadManager = $threadManager;
        $this->chat = $chat;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::USER_CREATED => array('onUserCreated'),
            \AppEvents::GROUP_ADDED => array('onGroupAdded'),
            \AppEvents::PROFILE_CREATED => array('onProfileCreated'),
        );
    }

    public function onUserCreated(UserEvent $event)
    {
        $user = $event->getUser();
        //$threads = $this->threadManager->getDefaultThreads($user);

        //$createdThreads = $this->threadManager->createBatchForUser($user->getId(), $threads);
        // TODO: Enqueue thread recommendation
    }

    public function onGroupAdded(GroupEvent $groupEvent)
    {
        $userId = $groupEvent->getUserId();
        $group = $groupEvent->getGroup();

        $this->threadManager->create($userId, $this->threadManager->getGroupThreadData($group, $userId));
    }

    public function onProfileCreated(ProfileEvent $profileEvent)
    {
        $profile = $profileEvent->getProfile();
        $id = $profileEvent->getUserId();

        if (!$id || !$profile){
            return false;
        }

        $locale = isset($profile['interfaceLanguage']) ? $profile['interfaceLanguage'] : 'en';

        try {
            $this->chat->createDefaultMessage($id, $locale);
        } catch (RequestException $e) {

        }

        return true;
    }
}