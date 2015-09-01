<?php


namespace Event;

use Symfony\Component\EventDispatcher\Event;

class UserStatusChangedEvent extends Event
{

    protected $userId;

    protected $status;

    public function __construct($userId, $status)
    {
        $this->userId = (integer)$userId;
        $this->status = $status;
    }

    public function getUserId()
    {

        return $this->userId;
    }

    public function getStatus()
    {

        return $this->status;
    }

}
