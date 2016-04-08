<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Event;


use Model\User;
use Symfony\Component\EventDispatcher\Event;

class GroupEvent extends Event
{
    protected $group;
    protected $user;

    public function __construct(array $group, User $user = null)
    {
        $this->group = $group;
        $this->user = $user;
    }

    /**
     * @return array
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

}