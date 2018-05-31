<?php

namespace Tests\API\Questions;

class QuestionsTest extends QuestionsAPITest
{
    protected $nextQuestionId;

    public function testQuestions()
    {
        $this->assertQuestionCreation();
        $this->assertQuestionCreationFromAdmin();
        $this->assertNextQuestion();
        $this->assertSkipQuestion();
        $this->assertReportQuestions();
    }

    public function assertQuestionCreation()
    {
        $questionData = $this->getCreateQuestionDataA();
        $response = $this->createQuestion($questionData, 999);
        $this->assertStatusCode($response, 401, 'Question creation from non-existent user');

        $response = $this->createQuestion($questionData, 1);
        $this->assertStatusCode($response, 201, 'Correct question creation');
    }

    public function assertQuestionCreationFromAdmin()
    {
        $questionData = $this->getCreateQuestionDataFromAdminA();
        $response = $this->createQuestionFromAdmin($questionData);
        $this->assertStatusCode($response, 201, 'Correct question creation from admin');

        $questionData = $this->getCreateQuestionDataFromAdmin1Answer();
        $response = $this->createQuestionFromAdmin($questionData);
        $this->assertStatusCode($response, 422, 'Incorrect question creation from admin with 1 answer');

        $questionData = $this->getCreateQuestionDataFromAdmin7Answers();
        $response = $this->createQuestionFromAdmin($questionData);
        $this->assertStatusCode($response, 422, 'Incorrect question creation from admin with 7 answers');

        $questionData = $this->getCreateQuestionDataFromAdminInvalidLocale();
        $response = $this->createQuestionFromAdmin($questionData);
        $this->assertStatusCode($response, 422, 'Incorrect question creation from admin with invalid locale');

        $questionData = $this->getCreateQuestionDataFromAdminInvalidText();
        $response = $this->createQuestionFromAdmin($questionData);
        $this->assertStatusCode($response, 422, 'Incorrect question creation from admin with invalid text');
    }

    public function assertGetQuestionsFromAdmin()
    {
        $response = $this->getQuestionsFromAdmin();
        $formattedResponse = $this->assertJsonResponse($response, 200, 'Getting questions from admin');
        $this->isType('array')->evaluate($formattedResponse, 'Questions from admin return an array');


    }

    public function assertNextQuestion()
    {
        $response = $this->getNextOwnQuestion();
        $questionData = $this->assertJsonResponse($response, 200, 'Getting own next question');
        $this->assertQuestionFormat($questionData);
        $this->nextQuestionId = $questionData['questionId'];
    }

    public function assertSkipQuestion()
    {
        $response = $this->skipQuestion($this->nextQuestionId);
        $this->assertStatusCode($response, 201, 'Skipping question response');

        $response = $this->getNextOwnQuestion();
        $this->assertStatusCode($response, 200, 'Next question after skipped');
    }

    public function assertReportQuestions()
    {
        $response = $this->reportQuestion($this->nextQuestionId);
        $this->assertStatusCode($response, 201, 'Correctly reported question');
        //TODO: Get own answers up and then check it does not appear anymore
    }

    protected function getCreateQuestionDataA()
    {
        $answers = array(
            array('text' => 'Answer 1 to question A'),
            array('text' => 'Answer 2 to question A'),
            array('text' => 'Answer 3 to question A'),
        );

        return array(
            'locale' => 'en',
            'text' => 'English text question A',
            'answers' => $answers
        );
    }

    protected function getCreateQuestionDataFromAdminA()
    {
        return array(
            'textEs' => 'Question text in Spanish',
            'textEn' => 'Question text in English',
            'answer1Es' => 'Answer 1 text in Spanish',
            'answer1En' => 'Answer 1 text in English',
            'answer2Es' => 'Answer 2 text in Spanish',
            'answer2En' => 'Answer 2 text in English',
        );
    }

    protected function getCreateQuestionDataFromAdmin1Answer()
    {
        return array(
            'textEs' => 'Question text in Spanish',
            'textEn' => 'Question text in English',
            'answer1Es' => 'Answer 1 text in Spanish',
            'answer1En' => 'Answer 1 text in English',
        );
    }

    protected function getCreateQuestionDataFromAdmin7Answers()
    {
        return array(
            'textEs' => 'Question text in Spanish',
            'textEn' => 'Question text in English',
            'answer1Es' => 'Answer 1 text in Spanish',
            'answer1En' => 'Answer 1 text in English',
            'answer2Es' => 'Answer 2 text in Spanish',
            'answer2En' => 'Answer 2 text in English',
            'answer3Es' => 'Answer 3 text in Spanish',
            'answer3En' => 'Answer 3 text in English',
            'answer4Es' => 'Answer 4 text in Spanish',
            'answer4En' => 'Answer 4 text in English',
            'answer5Es' => 'Answer 5 text in Spanish',
            'answer5En' => 'Answer 5 text in English',
            'answer6Es' => 'Answer 6 text in Spanish',
            'answer6En' => 'Answer 6 text in English',
            'answer7Es' => 'Answer 7 text in Spanish',
            'answer7En' => 'Answer 7 text in English',
        );
    }

    protected function getCreateQuestionDataFromAdminInvalidLocale()
    {
        return array(
            'textEs' => 'Question text in Spanish',
            'textEn' => 'Question text in English',
            'answer1Sp' => 'Answer 1 text in Spanish',
            'answer1En' => 'Answer 1 text in English',
            'answer2Es' => 'Answer 2 text in Spanish',
            'answer2En' => 'Answer 2 text in English',
        );
    }

    protected function getCreateQuestionDataFromAdminInvalidText()
    {
        return array(
            'textEs' => array('Question text in Spanish'),
            'textEn' => 'Question text in English',
            'answer1Es' => 'Answer 1 text in Spanish',
            'answer1En' => 'Answer 1 text in English',
            'answer2Es' => 'Answer 2 text in Spanish',
            'answer2En' => 'Answer 2 text in English',
        );
    }

}