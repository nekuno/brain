<?php


namespace Event;

use Symfony\Component\EventDispatcher\Event;

class FetchEvent extends Event
{

    protected $user;

    protected $resourceOwner;

    public function __construct($user, $resourceOwner)
    {

        $this->user = $user;
        $this->resourceOwner = $resourceOwner;
    }

    public function getUser()
    {

        return $this->user;
    }

    public function getResourceOwner()
    {

        return $this->resourceOwner;
    }

}
