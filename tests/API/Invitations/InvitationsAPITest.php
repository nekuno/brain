<?php

namespace Tests\API\Profile;

use Tests\API\APITest;

abstract class InvitationsAPITest extends APITest
{
    protected function getInvitations($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/invitations', 'GET', array(), $loggedInUserId);
    }

    protected function createInvitation($data, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/invitations' , 'POST', $data, $loggedInUserId);
    }
}