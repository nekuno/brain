<?php

namespace Controller\Questionnaire;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Questionnaire\QuestionModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class QuestionController
 * @package Controller\Questionnaire
 */
class QuestionController
{

    /**
     * Returns an unanswered question for given user
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function getNextQuestionAction(Request $request, Application $app)
    {

        $userId = $request->query->get('userId');

        if (null === $userId) {
            return $app->json(array(), 400);
        }

        $locale = $this->getLocale($request, $app['locale.options']['default']);

        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];
        $result = $model->getNextByUser($userId, $locale);

        $question = array();

        foreach ($result as $row) {

            /* @var $row Row */
            $node = $row->current();
            /* @var $node Node */

            $question['id'] = $node->getId();
            $question['text'] = $node->getProperty('text_' . $locale);
            $question['locale'] = $locale;

            foreach ($row['nextAnswers'] as $answer) {
                /* @var $answer Node */
                $question['answers'][$answer->getId()] = $answer->getProperty('text_' . $locale);
            }
        }

        if (!empty($question)) {
            return $app->json($question, 200);
        } else {
            return $app->json(array(), 404);
        }
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function getQuestionsAction(Request $request, Application $app)
    {

        $locale = $this->getLocale($request, $app['locale.options']['default']);
        $limit = $request->query->get('limit', 20);

        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];
        $result = $model->getAll($locale, $limit);

        $questions = array();

        foreach ($result as $row) {

            /* @var $row Row */
            $node = $row->current();
            /* @var $node Node */
            $question['id'] = $node->getId();
            $question['text'] = $node->getProperty('text_' . $locale);
            $question['locale'] = $locale;
            $question['answers'] = array();
            foreach ($row['answers'] as $answer) {
                /* @var $answer Node */
                $question['answers'][$answer->getId()] = $answer->getProperty('text_' . $locale);
            }
            $questions[] = $question;
        }

        if (!empty($questions)) {
            return $app->json($questions, 200);
        } else {
            return $app->json(array(), 404);
        }
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function getQuestionAction(Request $request, Application $app)
    {

        $locale = $this->getLocale($request, $app['locale.options']['default']);
        $questionId = $request->get('id');

        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];
        $result = $model->getById($questionId, $locale);

        $question = array();

        foreach ($result as $row) {

            /* @var $row Row */
            $node = $row->current();
            /* @var $node Node */
            $question['id'] = $node->getId();
            $question['text'] = $node->getProperty('text_' . $locale);
            $question['locale'] = $locale;

            foreach ($row['answers'] as $answer) {
                /* @var $answer Node */
                $question['answers'][$answer->getId()] = $answer->getProperty('text_' . $locale);
            }
        }

        if (!empty($question)) {
            return $app->json($question, 200);
        } else {
            return $app->json(array(), 404);
        }
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function postQuestionAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        if (false === $this->validateQuestion($data)) {
            return $app->json(array('error' => 'Invalid question data passed'), 400);
        }

        try {
            /* @var QuestionModel $model */
            $model = $app['questionnaire.questions.model'];
            $result = $model->create($data);
            if (null !== $result) {
                return $app->json(array('Resource created successfully'), 201);
            }
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array($e->getMessage()), 500);
        }

        $app->json(array(), 200);
    }

    /**
     * @param array $data
     * @return bool
     */
    private function validateQuestion(array $data)
    {

        if (empty($data)) {
            return false;
        } elseif (!array_key_exists('text', $data) || !array_key_exists('answers', $data)) {
            return false;
        } elseif ($data['text'] === null || $data['text'] == '') {
            return false;
        } elseif (!is_array($data['answers'])) {
            return false;
        } elseif (empty($data['answers']) || count($data['answers']) < 2) {
            return false;
        }

        return true;
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function skipAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        if (!$model->existsQuestion($data['questionId'])) {
            return $app->json(array('error' => "Given question doesn't exists"), 404);
        }

        try {
            $model->skip($data);
        } catch (\Exception $e) {
            return $app->json(array('error' => 'Error skipping question'), 500);
        }

        return $app->json(array('Question skipped successfully'), 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function reportAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        if (!$model->existsQuestion($data['questionId'])) {
            return $app->json(array('error' => "Given question doesn't exists"), 404);
        }

        try {
            $model->report($data);
        } catch (\Exception $e) {
            return $app->json(array('error' => 'Error reporting question'), 500);
        }

        return $app->json(array('Question reported successfully'), 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function statsAction(Request $request, Application $app)
    {

        $id = $request->get('id');

        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        if (!$model->existsQuestion($id)) {
            return $app->json(array('error' => "Given question doesn't exists"), 404);
        }

        try {
            $stats = array();

            $result = $model->getQuestionStats($id);
            foreach ($result as $row) {
                $stats[$id]['answers'][$row['answer']] = array(
                    'id' => $row['answer'],
                    'nAnswers' => $row['nAnswers'],
                );
                if (isset($stats[$id]['totalAnswers'])) {
                    $stats[$id]['totalAnswers'] += $row['nAnswers'];
                } else {
                    $stats[$id]['totalAnswers'] = $row['nAnswers'];
                }

                $stats[$id]['id'] = $id;
            }

            return $app->json($stats, 200);

        } catch (\Exception $e) {
            return $app->json(array('error' => 'Error retrieving stats'), 500);
        }

    }

    protected function getLocale(Request $request, $defaultLocale)
    {
        $locale = $request->query->get('locale', $defaultLocale);
        if (!in_array($locale, array('en', 'es'))) {
            $locale = $defaultLocale;
        }

        return $locale;
    }

}