<?php


namespace ApiConsumer\Event;


use Symfony\Component\EventDispatcher\Event;

class OAuthTokenEvent extends Event
{

    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
