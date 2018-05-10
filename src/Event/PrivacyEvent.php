<?php

namespace Event;

use Symfony\Component\EventDispatcher\Event;

class PrivacyEvent extends Event
{

    protected $userId;
    protected $privacy;

    public function __construct($userId, $privacy)
    {
        $this->userId = $userId;
        $this->privacy = $privacy;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return mixed
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }

    /**
     * @param mixed $privacy
     */
    public function setPrivacy($privacy)
    {
        $this->privacy = $privacy;
    }



}
