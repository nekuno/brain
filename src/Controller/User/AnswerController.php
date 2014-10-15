<?php

namespace Controller\User;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\User\AnswerModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class AnswerController
{

    public function createAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        if (null === $data || array() === $data) {
            return $app->json(array(), 400);
        }

        try {
            /** @var AnswerModel $model */
            $model = $app['users.answers.model'];
            $model->create($data);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array($e->getMessage()), 500);
        }

        return $app->json(array('Resource created successful'), 201);
    }

    public function updateAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        if($data['userId'] != $request->get('userId')) {
            return $app->json('Invalid data', 400);
        }

        try {
            /** @var AnswerModel $model */
            $model = $app['users.answers.model'];
            $model->update($data);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array($e->getMessage()), 500);
        }

        return $app->json(array("Resource updated successful"), 200);
    }


    public function explainAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        try {
            /** @var AnswerModel $model */
            $model = $app['users.answers.model'];
            $model->explain($data);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array($e->getMessage()), 500);
        }

        return $app->json(array("Resource updated successful"), 200);
    }

    public function indexAction(Request $request, Application $app)
    {

        $userId = $request->get('userId');

        try {
            /** @var AnswerModel $model */
            $model = $app['users.answers.model'];
            $userAnswerResult = $model->getUserAnswers($userId);

            $questions = array();

            foreach ($userAnswerResult as $row) {
                $questionAnswers = array();
                /** @var Row $row */
                foreach ($row['answers'] as $answer) {
                    /** @var Node $answer */
                    $questionAnswers[$answer->getId()] = array(
                        'id' => $answer->getId(),
                        'text' => $answer->getProperty('text'),

                    );
                }
                $questions[$row['question']->getId()] = array(
                    'id' => $row['question']->getId(),
                    'text' => $row['question']->getProperty('text'),
                    'explanation' => $row['explanation'],
                    'answers' => $questionAnswers,
                    'userAnswer' => $row['answer']->getId(),
                    'answeredAt' => $row['answeredAt'] ? floor($row['answeredAt'] / 1000) : time(),
                );
            }

            if(empty($questions)){
                return $app->json('The user has not answered to any question', 404);
            }

            return $app->json($questions, 200);

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array($e->getMessage()), 500);
        }
    }

    public function countAction(Request $request, Application $app)
    {

        $userId = $request->get('userId');

        try {
            /** @var AnswerModel $model */
            $model = $app['users.answers.model'];
            $userAnswerResult = $model->getNumberOfUserAnswers($userId);

            $data = array(
                'userId' => $userId,
            );

            foreach ($userAnswerResult as $row) {
                $data['nOfAnswers'] = $row['nOfAnswers'];
            }

            if(empty($data)){
                return $app->json('The user has not answered to any question', 404);
            }

            return $app->json($data, 200);

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array($e->getMessage()), 500);
        }
    }
}
