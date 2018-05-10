<?php

namespace Event;

use Symfony\Component\EventDispatcher\Event;

class LookUpSocialNetworksEvent extends Event
{
    protected $userId;
    protected $socialNetworks = array();

    public function __construct($userId, array $socialNetworks)
    {

        $this->userId = (integer)$userId;
        $this->socialNetworks = $socialNetworks;
    }

    public function getUserId()
    {

        return $this->userId;
    }

    public function getSocialNetworks()
    {

        return $this->socialNetworks;
    }
}
