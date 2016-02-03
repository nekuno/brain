<?php


namespace Event;

use Symfony\Component\EventDispatcher\Event;

class AccountConnectEvent extends Event
{

    protected $userId;
    protected $resourceOwner;

    public function __construct($userId, $resourceOwner)
    {
        $this->userId = $userId;
        $this->resourceOwner = $resourceOwner;
    }

    public function getUserId()
    {

        return $this->userId;
    }

    public function getResourceOwner()
    {

        return $this->resourceOwner;
    }

}
