<?php

namespace Controller;

use Model\User;
use Silex\Application;

class BaseController
{
    /**
     * @var User $user
     */
    protected $user;

    public function __construct(User $user = null)
    {
        $this->user = $user;
    }

    protected function getUser()
    {
        return $this->user;
    }

    protected function getUserId()
    {
        return $this->user->getId();
    }
}
