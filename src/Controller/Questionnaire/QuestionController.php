<?php

namespace Controller\Questionnaire;

use Everyman\Neo4j\Query\Row;
use Model\Questionnaire\QuestionModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class QuestionController
{

    /**
     * Returns an unanswered question for given user
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function nextAction(Request $request, Application $app)
    {

        $userId = $request->query->get('userId');

        if (null === $userId) {
            return $app->json(array(), 400);
        }

        /** @var QuestionModel $model */
        $model = $app['questions.model'];
        $result = $model->getNextByUser($userId);

        if(null !== $result){
            $question = $this->buildQuestion($result);
            return $app->json($question, 200);
        } else {
            return $app->json(array(), 404);
        }
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function createAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        if (false === $this->isValidDataForCreateQuestion($data)) {
            return $app->json(array(), 400);
        }

        try {
            /** @var QuestionModel $model */
            $model = $app['questions.model'];
            $result = $model->create($data);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array($e->getMessage()), 500);
        }

        return $app->json(array('Resource created successful'), null === $result ? 201 : 200);

    }

    private function isValidDataForCreateQuestion(array $data)
    {

        if (empty($data)) {
            return false;
        } elseif (!array_key_exists('text', $data) || !array_key_exists('answers', $data)) {
            return false;
        } elseif (!is_array($data['answers'])) {
            return false;
        } elseif (empty($data['answers'])) {
            return false;
        }

        return true;
    }

    public function skipAction(Request $request, Application $app)
    {

        $data = $request->request->all();
        $data['questionId'] = (integer) $request->get('id');

        try {
            /** @var QuestionModel $model */
            $model = $app['questions.model'];
            $model->skip($data);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json(array('Question skipped successfully'), 200);
    }

    public function reportAction(Request $request, Application $app)
    {

        $data = $request->request->all();
        $data['questionId'] = (integer) $request->get('id');

        try {
            /** @var QuestionModel $model */
            $model = $app['questions.model'];
            $model->skip($data, $data['userId'], $data['reason']);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json(array('Question reported successfully'), 200);
    }

    private function buildQuestion(Row $node)
    {

        $question['id'] = $node['next']->getId();
        $question['text'] = $node['next']->getProperty('text');

        foreach ($node['nextAnswers'] as $answer) {
            $question['answers'][] = $answer->getProperty('text');
        }

        return $question;
    }
}