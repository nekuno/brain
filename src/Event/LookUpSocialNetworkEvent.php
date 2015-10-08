<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Event;

use Symfony\Component\EventDispatcher\Event;

class LookUpSocialNetworkEvent extends Event
{
    protected $userId;
    protected $profileUrl;

    public function __construct($userId, $profileUrl)
    {

        $this->userId = (integer)$userId;
        $this->profileUrl = $profileUrl;
    }

    public function getUserId()
    {

        return $this->userId;
    }

    public function getProfileUrl()
    {

        return $this->profileUrl;
    }
}
