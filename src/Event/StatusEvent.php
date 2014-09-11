<?php


namespace Event;

use Symfony\Component\EventDispatcher\Event;

class StatusEvent extends Event
{

    protected $user;

    protected $resourceOwner;

    public function __construct($user, $resourceOwner)
    {

        $this->user = $user;
        $this->resourceOwner = $resourceOwner;
    }

    /**
     * @return mixed
     */
    public function getResourceOwner()
    {

        return $this->resourceOwner;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {

        return $this->user;
    }
}
