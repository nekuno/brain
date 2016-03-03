<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Tests\API\Profile;

use Tests\API\APITest;

abstract class ProfileAPITest extends APITest
{
    protected function getOwnProfile($loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/profile', 'GET', array(), $loggedInUserId);
    }

    protected function getOtherProfile($userId, $loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/profile/' . $userId, 'GET', array(), $loggedInUserId);
    }

    protected function validateProfile($userData, $loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/profile/validate', 'POST', $userData, $loggedInUserId);
    }

    protected function createProfile($userData, $loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/profile', 'POST', $userData, $loggedInUserId);
    }

    protected function editProfile($userData, $loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/profile', 'PUT', $userData, $loggedInUserId);
    }

    protected function deleteProfile($loggedInUserId = 1)
    {
        return $this->getResponseByRoute('/profile', 'DELETE', array(), $loggedInUserId);
    }
}