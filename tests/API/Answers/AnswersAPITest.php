<?php

namespace Tests\API\Answers;

use Tests\API\APITest;

abstract class AnswersAPITest extends APITest
{

    public function getNextOwnQuestion($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/questions/next', 'GET', array(), $loggedInUserId);
    }

    public function getOwnAnswers($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/answers', 'GET', array(), $loggedInUserId);
    }

    public function answerQuestion($data, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/answers', 'POST', $data, $loggedInUserId);
    }

    protected function assertQuestionFormat($data)
    {

    }
}