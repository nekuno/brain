<?php

namespace Tests\API\Threads;

use Tests\API\APITest;

abstract class ThreadsAPITest extends APITest
{
    protected function getThreads($loggedInUser = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/threads', 'GET', array(), $loggedInUser);
    }

    protected function getRecommendations($threadId, $loggedInUser = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/threads/' . $threadId . '/recommendation', 'GET', array(), $loggedInUser);
    }

    protected function createThread($data, $loggedInUser = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/threads', 'POST', $data, $loggedInUser);
    }

    protected function editThread($data, $threadId, $loggedInUser = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/threads/' . $threadId, 'PUT', $data, $loggedInUser);
    }

    protected function deleteThread($threadId, $loggedInUser = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/threads/' . $threadId, 'DELETE', array(), $loggedInUser);
    }
}