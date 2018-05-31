<?php

namespace Tests\API\Answers;

class AnswersTest extends AnswersAPITest
{
    public function testAnswers()
    {
        $this->assertAnswer();
        $this->assertGetOwnAnswers();
    }

    public function assertGetOwnAnswers()
    {
        $response = $this->getOwnAnswers();
        $this->assertJsonResponse($response, 200, 'Getting own questions');
    }

    public function assertAnswer()
    {
        $answerData = $this->getAnswerData();
        $response = $this->answerQuestion($answerData);
        $this->assertJsonResponse($response, 201, 'Correctly answering a question');

        $response = $this->answerQuestion($answerData);
        $this->assertStatusCode($response, 422, 'Cannot answer again in less than 24 hours');
    }

    protected function getAnswerData()
    {
        $question = json_decode($this->getNextOwnQuestion()->getContent(), true);

        return array(
            'questionId' => $question['questionId'],
            'answerId' => $question['answers'][0]['answerId'],
            'acceptedAnswers' => array($question['answers'][0]['answerId']),
            'rating' => 2,
            'explanation' => '',
            'isPrivate' => false,
        );
    }

}