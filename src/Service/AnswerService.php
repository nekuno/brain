<?php

namespace Service;

use Everyman\Neo4j\Query\Row;
use Model\Question\Answer;
use Model\Question\AnswerBuilder;
use Model\Question\AnswerManager;
use Model\Question\QuestionManager;

class AnswerService
{
    protected $answerManager;

    protected $questionManager;

    protected $answerBuilder;

    /**
     * AnswerService constructor.
     * @param AnswerManager $answerManager
     * @param QuestionManager $questionManager
     */
    public function __construct(AnswerManager $answerManager, QuestionManager $questionManager)
    {
        $this->answerManager = $answerManager;
        $this->questionManager = $questionManager;
        $this->answerBuilder = new AnswerBuilder();
    }

    public function getUserAnswer($userId, $questionId, $locale)
    {
        $row = $this->answerManager->getUserAnswer($userId, $questionId, $locale);
        $answer = $this->build($row, $locale);

        return $answer;
    }

    public function create(array $data)
    {
        $row = $this->answerManager->create($data);
        return $this->build($row, $data['locale']);
    }

    public function update(array $data)
    {
        $data = $this->addUserAnswer($data);
        $row = $this->answerManager->update($data);
        return $this->build($row, $data['locale']);
    }

    public function answer(array $data)
    {
        if ($this->answerManager->isQuestionAnswered($data['userId'], $data['questionId'])) {
            return $this->update($data);
        } else {
            return $this->create($data);
        }
    }

    public function explain(array $data)
    {
        $row = $this->answerManager->explain($data);
        return $this->build($row, $data['locale']);
    }

    public function build(Row $row, $locale)
    {
        return array(
            'userAnswer' => $this->answerBuilder->buildUserAnswer($row),
            'question' => $this->questionManager->build($row, $locale),
        );
    }

    protected function addUserAnswer(array $data)
    {
        $answerResult = $this->getUserAnswer($data['userId'], $data['questionId'], $data['locale']);
        /** @var Answer $answer */
        $answer = $answerResult['userAnswer'];
        $this->answerBuilder->updateEditable($answer);

        $data['userAnswer'] = $answer;

        return $data;
    }


}