<?php

namespace Tests\API\Similarity;

use Tests\API\APITest;

abstract class SimilarityAPITest extends APITest
{
    public function getSimilarity($otherUserId)
    {
        return $this->getResponseByRouteWithCredentials('/similarity/'.$otherUserId);
    }

    public function getMatching($otherUserId)
    {
        return $this->getResponseByRouteWithCredentials('/matching/'.$otherUserId);
    }
}