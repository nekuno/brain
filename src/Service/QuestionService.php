<?php

namespace Service;

use Model\Question\Admin\QuestionAdminDataFormatter;
use Model\Question\Admin\QuestionAdminManager;
use Model\Question\QuestionCategory\QuestionCategoryManager;
use Model\Question\QuestionCorrelationManager;
use Model\Question\QuestionManager;
use Model\Question\QuestionNextSelector;

class QuestionService
{
    /**
     * @var QuestionManager
     */
    protected $questionManager;

    /**
     * @var QuestionAdminManager
     */
    protected $questionAdminManager;

    protected $questionCategoryManager;

    protected $questionAdminDataFormatter;
    
    protected $questionNextSelector;

    protected $questionCorrelationManager;

    /**
     * @param QuestionManager $questionManager
     * @param QuestionAdminManager $questionAdminManager
     * @param QuestionCategoryManager $questionCategoryManager
     * @param QuestionNextSelector $questionNextSelector
     * @param QuestionCorrelationManager $questionCorrelationManager
     */
    public function __construct(QuestionManager $questionManager, QuestionAdminManager $questionAdminManager, QuestionCategoryManager $questionCategoryManager, QuestionNextSelector $questionNextSelector, QuestionCorrelationManager $questionCorrelationManager)
    {
        $this->questionManager = $questionManager;
        $this->questionAdminManager = $questionAdminManager;
        $this->questionCategoryManager = $questionCategoryManager;
        $this->questionNextSelector = $questionNextSelector;
        $this->questionCorrelationManager = $questionCorrelationManager;
        $this->questionAdminDataFormatter = new QuestionAdminDataFormatter();
    }

    public function createQuestion(array $data)
    {
        $data = $this->questionAdminDataFormatter->getCreateData($data);
        $created = $this->questionAdminManager->create($data);
        $questionId = $created->getQuestionId();
        $this->questionCategoryManager->setQuestionCategories($questionId, $data);

        return $this->getOneAdmin($questionId);
    }

    public function updateQuestion(array $data)
    {
        $data = $this->questionAdminDataFormatter->getUpdateData($data);
        $created = $this->questionAdminManager->update($data);
        $questionId = $created->getQuestionId();
        $this->questionCategoryManager->setQuestionCategories($questionId, $data);

        return $this->getOneAdmin($questionId);
    }

    public function getById($questionId, $locale)
    {
        return $this->questionManager->getById($questionId, $locale);

    }

    public function getOneAdmin($questionId)
    {
        return $this->questionAdminManager->getById($questionId);
    }

    public function deleteQuestion($questionId)
    {
        if (null === $questionId) {
            return false;
        }

        $data = array('questionId' => $questionId);

        return $this->questionManager->delete($data);
    }
    
    public function getNextByUser($userId, $locale, $sortByRanking = true)
    {
        $row = $this->questionNextSelector->getNextByUser($userId, $locale, $sortByRanking);
        return $this->questionManager->build($row, $locale);
    }

    public function getNextByOtherUser($userId, $otherUserId, $locale, $sortByRanking = true)
    {
        $row = $this->questionNextSelector->getNextByOtherUser($userId, $otherUserId, $locale, $sortByRanking);
        return $this->questionManager->build($row, $locale);
    }

    public function getDivisiveQuestions($locale)
    {
        $result = $this->questionCorrelationManager->getDivisiveQuestions($locale);

        $questions = array();
        foreach ($result as $row)
        {
            $questions[] = $this->questionManager->build($row, $locale);
        }

        return $questions;
    }

    public function createQuestionCategories()
    {
        $this->questionCategoryManager->createQuestionCategoriesFromModes();
    }
}