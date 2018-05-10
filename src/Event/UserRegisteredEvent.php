<?php

namespace Event;

use Model\Profile\Profile;
use Model\Token\Token;
use Model\User\User;

class UserRegisteredEvent extends UserEvent
{
    protected $profile;
    protected $invitation;
    protected $token;
    protected $trackingData;

    public function __construct(User $user, Profile $profile, $invitation, Token $token, $trackingData)
    {
        parent::__construct($user);
        $this->profile = $profile;
        $this->invitation = $invitation;
        $this->token = $token;
        $this->trackingData = $trackingData;
    }

    public function getProfile()
    {
        return $this->profile;
    }

    public function getInvitation()
    {
        return $this->invitation;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getTrackingData()
    {
        return $this->trackingData;
    }
}
