<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Tests\API;

abstract class UsersAPITest extends APITest
{
    protected function getUserAvailable($username)
    {
        return $this->getResponseByRoute('/users/available/' . $username);
    }

    protected function validateUserA($userData)
    {
        return $this->getResponseByRoute('/users/validate', 'POST', $userData);
    }

    protected function createUser($userData)
    {
        return $this->getResponseByRoute('/users', 'POST', $userData);
    }

    protected function editOwnUser($userData)
    {
        return $this->getResponseByRoute('/users', 'PUT', $userData, 1);
    }

    protected function loginUserA($userData)
    {
        return $this->getResponseByRoute('/login', 'OPTIONS', $userData);
    }

    protected function getOwnUser()
    {
        return $this->getResponseByRoute('/users', 'GET', array(), 1);
    }

    protected function getOtherUser($userId)
    {
        return $this->getResponseByRoute('/users/' . $userId, 'GET', array(), 1);
    }
}