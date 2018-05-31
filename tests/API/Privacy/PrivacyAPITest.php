<?php

namespace Tests\API\Privacy;

use Tests\API\APITest;

abstract class PrivacyAPITest extends APITest
{
    protected function getOwnPrivacy($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/privacy', 'GET', array(), $loggedInUserId);
    }

    protected function validatePrivacy($userData, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/privacy/validate', 'POST', $userData, $loggedInUserId);
    }

    protected function createPrivacy($userData, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/privacy', 'POST', $userData, $loggedInUserId);
    }

    protected function editPrivacy($userData, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/privacy', 'PUT', $userData, $loggedInUserId);
    }

    protected function deletePrivacy($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/privacy', 'DELETE', array(), $loggedInUserId);
    }

    protected function getPrivacyMetadata($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/privacy/metadata', 'GET', array(), $loggedInUserId);
    }
}