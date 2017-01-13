<?php

namespace Tests\API\Privacy;

use Tests\API\APITest;

abstract class PrivacyAPITest extends APITest
{
    protected function getOwnPrivacy($loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/privacy', 'GET', array(), $loggedInUserId);
    }

    protected function validatePrivacy($userData, $loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/privacy/validate', 'POST', $userData, $loggedInUserId);
    }

    protected function createPrivacy($userData, $loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/privacy', 'POST', $userData, $loggedInUserId);
    }

    protected function editPrivacy($userData, $loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/privacy', 'PUT', $userData, $loggedInUserId);
    }

    protected function deletePrivacy($loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/privacy', 'DELETE', array(), $loggedInUserId);
    }

    protected function getPrivacyMetadata($loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/privacy/metadata', 'GET', array(), $loggedInUserId);
    }
}