<?php

namespace Tests\API\Matching;

use Tests\API\APITest;

abstract class MatchingAPITest extends APITest
{
    public function getMatching($otherUserId)
    {
        return $this->getResponseByRouteWithCredentials('/matching/'.$otherUserId);
    }
}