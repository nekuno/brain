<?php


namespace Event;

use Symfony\Component\EventDispatcher\Event;

class ContentRatedEvent extends Event
{

    protected $user;

    public function __construct($user)
    {

        $this->user = $user;
    }

    public function getUser()
    {

        return $this->user;
    }
}
