<?php

namespace Event;

use Model\User\Group\Group;
use Symfony\Component\EventDispatcher\Event;

class GroupEvent extends Event
{
    protected $group;
    protected $userId;

    public function __construct(Group $group, $userId = null)
    {
        $this->group = $group;
        $this->userId = $userId;
    }

    /**
     * @return Group
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