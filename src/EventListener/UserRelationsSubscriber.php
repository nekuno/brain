<?php

namespace EventListener;

use Event\UserBothLikedEvent;
use GuzzleHttp\Exception\RequestException;
use Model\User\UserManager;
use Service\DeviceService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserRelationsSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var DeviceService
     */
    protected $deviceService;

    public function __construct(UserManager $userManager, DeviceService $deviceService)
    {
        $this->userManager = $userManager;
        $this->deviceService = $deviceService;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::USER_BOTH_LIKED => array('onUserBothLiked'),
        );
    }

    public function onUserBothLiked(UserBothLikedEvent $event)
    {
        $userFromId = $event->getUserFromId();
        $userFrom = $this->userManager->getById($userFromId);
        $userToId = $event->getUserToId();
        $userTo = $this->userManager->getById($userToId);

        $dataTo = array(
            'slug' => $userFrom->getSlug(),
            'username' => $userFrom->getUsername(),
            'image' => $userFrom->getPhoto()->jsonSerialize()['thumbnail']['big'],
        );
        $dataFrom = array(
            'slug' => $userTo->getSlug(),
            'username' => $userTo->getUsername(),
            'image' => $userTo->getPhoto()->jsonSerialize()['thumbnail']['big'],
        );
        try {
            $this->deviceService->pushMessage($dataTo, $userToId, DeviceService::BOTH_USER_LIKED_CATEGORY);
            $this->deviceService->pushMessage($dataFrom, $userFromId, DeviceService::BOTH_USER_LIKED_CATEGORY);
        } catch (RequestException $e) {

        }
    }
}