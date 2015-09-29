<?php


namespace ApiConsumer\Event;

use Symfony\Component\EventDispatcher\Event;

class OAuthTokenEvent extends Event
{

    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }
}
