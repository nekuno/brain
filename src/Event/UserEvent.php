<?php

namespace Event;

use Model\User\User;
use Symfony\Component\EventDispatcher\Event;

class UserEvent extends Event
{

    /**
     * @var User
     */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {

        return $this->user;
    }

}
