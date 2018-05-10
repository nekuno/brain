<?php

namespace Event;

use Model\Token\Token;
use Symfony\Component\EventDispatcher\Event;

class AccountConnectEvent extends Event
{

    protected $userId;
    /** @var  Token */
    protected $token;

    public function __construct($userId, $token)
    {
        $this->userId = $userId;
        $this->token = $token;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getToken()
    {
        return $this->token;
    }
}
