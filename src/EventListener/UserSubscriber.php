<?php

namespace EventListener;

use Event\GroupEvent;
use Event\ProfileEvent;
use Event\UserEvent;
use Event\UserRegisteredEvent;
use GuzzleHttp\Exception\RequestException;
use Model\Group\Group;
use Model\Thread\ThreadManager;
use Service\ChatMessageNotifications;
use Service\InstantConnection;
use Service\ThreadService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserSubscriber implements EventSubscriberInterface
{
    protected $threadService;

    protected $chat;

    protected $instantConnection;

    public function __construct(ThreadService $threadService, ChatMessageNotifications $chat, InstantConnection $instantConnection)
    {
        $this->threadService = $threadService;
        $this->chat = $chat;
        $this->instantConnection = $instantConnection;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::USER_CREATED => array('onUserCreated'),
            \AppEvents::GROUP_ADDED => array('onGroupAdded'),
            \AppEvents::GROUP_REMOVED => array('onGroupRemoved'),
            \AppEvents::PROFILE_CREATED => array('onProfileCreated'),
            \AppEvents::USER_REGISTERED => array('onUserRegistered'),
            \AppEvents::USER_PHOTO_CHANGED => array('onUserPhotoChanged'),
        );
    }

    public function onUserCreated(UserEvent $event)
    {
    }

    public function onGroupAdded(GroupEvent $groupEvent)
    {
        $userId = $groupEvent->getUserId();
        $group = $groupEvent->getGroup();

        $this->threadService->createGroupThread($group, $userId);
    }

    public function onGroupRemoved(GroupEvent $groupEvent)
    {
        $groupId = $groupEvent->getGroup()->getId();
        $userId = $groupEvent->getUserId();

//        $this->threadManager->deleteGroupThreads($userId, $groupId);
    }

    public function onProfileCreated(ProfileEvent $profileEvent)
    {
        $profile = $profileEvent->getProfile();
        $id = $profileEvent->getUserId();

        if (!$id || !$profile){
            return false;
        }

        $locale = $profile->get('interfaceLanguage') ?: 'en';

        try {
            $this->chat->createDefaultMessage($id, $locale);
        } catch (RequestException $e) {
            return false;
        }

        return true;
    }

    public function onUserRegistered(UserRegisteredEvent $event)
    {
        $user = $event->getUser();
        $scenario = ThreadManager::SCENARIO_DEFAULT_LITE;
        $this->threadService->createDefaultThreads($user->getId(), $scenario);

        $this->checkIfGroupRegister($event);
    }

    protected function checkIfGroupRegister(UserRegisteredEvent $event)
    {
        $user = $event->getUser();
        $invitation = $event->getInvitation();

        /** @var Group $group */
        $group = isset($invitation['invitation']['group']) ? $invitation['invitation']['group'] : null;

        if ($group) {
            $this->threadService->createGroupThread($group, $user->getId());
        }
    }

    public function onUserPhotoChanged(UserEvent $event)
    {
        $user = $event->getUser();
        $json = array('userId' => $user->getId());
        $this->instantConnection->clearUser($json);
    }
}