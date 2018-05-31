<?php

namespace Tests\API\Questions;

use Tests\API\APITest;

abstract class QuestionsAPITest extends APITest
{
    public function createQuestion($questionData, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/questions', 'POST', $questionData, $loggedInUserId);
    }

    public function createQuestionFromAdmin($questionData)
    {
        return $this->getResponseByRouteWithoutCredentials('/admin/questions', 'POST', $questionData);
    }

    public function getQuestionsFromAdmin()
    {
        return $this->getResponseByRouteWithoutCredentials('admin/questions');
    }

    public function reportQuestion($questionId, $loggedInUserId = self::OWN_USER_ID)
    {
        $url = '/questions/' . $questionId . '/report';

        return $this->getResponseByRouteWithCredentials($url, 'POST', array(), $loggedInUserId);
    }

    public function getNextOwnQuestion($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/questions/next', 'GET', array(), $loggedInUserId);
    }

    public function skipQuestion($questionId, $loggedInUserId = self::OWN_USER_ID)
    {
        $url = '/questions/' . $questionId . '/skip';

        return $this->getResponseByRouteWithCredentials($url, 'POST', array(), $loggedInUserId);
    }

    protected function assertQuestionFormat($data)
    {

    }
}