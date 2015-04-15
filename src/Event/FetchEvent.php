<?php


namespace Event;

use Symfony\Component\EventDispatcher\Event;

class FetchEvent extends Event
{

    protected $user;

    protected $resourceOwner;

    protected $fetcher;

    public function __construct($user, $resourceOwner, $fetcher)
    {

        $this->user = $user;
        $this->resourceOwner = $resourceOwner;
        $this->fetcher = $fetcher;
    }

    public function getUser()
    {

        return $this->user;
    }

    public function getResourceOwner()
    {

        return $this->resourceOwner;
    }

    public function getFetcher()
    {
        return $this->fetcher;
    }

}
