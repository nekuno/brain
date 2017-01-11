<?php

namespace Event;


use Model\User;
use Symfony\Component\EventDispatcher\Event;

class ProfileEvent extends Event
{
    /**
     * @var int
     */
    protected $userId;

    /**
     * @var array
     */
    protected $profile;

    public function __construct(array $profile, $userId = null)
    {
        $this->profile = $profile;
        $this->userId = $userId;
    }

    /**
     * @return array
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @param array $profile
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }
}