<?php

namespace Event;


use Model\Profile\Profile;
use Model\User\User;
use Symfony\Component\EventDispatcher\Event;

class ProfileEvent extends Event
{
    /**
     * @var int
     */
    protected $userId;

    /**
     * @var Profile
     */
    protected $profile;

    public function __construct(Profile $profile, $userId = null)
    {
        $this->profile = $profile;
        $this->userId = $userId;
    }

    /**
     * @return Profile
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