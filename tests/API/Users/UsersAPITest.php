<?php

namespace Tests\API\Users;

use Tests\API\APITest;

abstract class UsersAPITest extends APITest
{
    protected function getUserAvailable($username)
    {
        return $this->getResponseByRouteWithoutCredentials('/users/available/' . $username);
    }

    protected function validateUserA($userData)
    {
        return $this->getResponseByRouteWithoutCredentials('/users/validate', 'POST', $userData);
    }

    protected function editOwnUser($userData, $loggedInUser = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/users', 'PUT', $userData, $loggedInUser);
    }

    protected function getOwnUser($loggedInUser = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/users', 'GET', array(), $loggedInUser);
    }

    protected function getOwnUserStatus($loggedInUser = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/data/status', 'GET', array(), $loggedInUser);
    }

    protected function getOtherUser($slug, $loggedInUser = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/users/' . $slug, 'GET', array(), $loggedInUser);
    }

    protected function loginUser($userData)
    {
        return $this->getResponseByRouteWithoutCredentials('/login', 'POST', $userData);
    }

    protected function createUser($userData)
    {
        return $this->getResponseByRouteWithoutCredentials('/register', 'POST', $userData);
    }

    protected function deleteUserFromAdmin($userId)
    {
        $url = sprintf('/admin/users/%d', $userId);
        return $this->getResponseByRouteWithoutCredentials($url, 'DELETE');
    }

}