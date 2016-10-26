<?php

namespace Event;


use Model\User;
use Symfony\Component\EventDispatcher\Event;

class GroupEvent extends Event
{
    protected $group;
    protected $userId;

    public function __construct(array $group, $userId = null)
    {
        $this->group = $group;
        $this->userId = $userId;
    }

    /**
     * @return array
     */
    public function getGroup()
    {
        return $this->group;
    }

    public function getUserId()
    {
        return $this->userId;
    }

}