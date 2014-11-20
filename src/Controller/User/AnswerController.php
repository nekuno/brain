<?php

namespace Controller\User;

use Event\AnswerEvent;
use Model\Questionnaire\QuestionModel;
use Model\User\AnswerModel;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AnswerController
 * @package Controller\User
 */
class AnswerController
{

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function createAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        /** @var AnswerModel $model */
        $model = $app['users.answers.model'];

        if ($data['userId'] != $request->get('userId')) {
            return $app->json(array('errors' => 'User ids mismatch'), 400);
        }

        if (count($errors = $model->validate($data))) {
            return $app->json(array('errors' => $errors), 400);
        }

        try {
            $model->create($data);

            /** @var QuestionModel $questionModel */
            $questionModel = $app['questionnaire.questions.model'];
            $questionModel->setOrUpdateRankingForQuestion($data['questionId']);

            $event = new AnswerEvent($data['userId'], $data['questionId']);
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $app['dispatcher'];
            $dispatcher->dispatch(\AppEvents::USER_ANSWER_QUESTION, $event);

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array($e->getMessage()), 500);
        }

        return $app->json(array('Resource created successful'), 201);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function updateAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        if ($data['userId'] != $request->get('userId')) {
            return $app->json(array('errors' => 'User mismatch'), 400);
        }

        /** @var AnswerModel $model */
        $model = $app['users.answers.model'];

        if (count($errors = $model->validate($data))) {
            return $app->json(array('errors' => $errors), 400);
        }

        try {
            $model->update($data);

            /** @var QuestionModel $questionModel */
            $questionModel = $app['questionnaire.questions.model'];
            $questionModel->setOrUpdateRankingForQuestion($data['questionId']);

            $event = new AnswerEvent($data['userId'], $data['questionId']);
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $app['dispatcher'];
            $dispatcher->dispatch(\AppEvents::USER_ANSWER_QUESTION, $event);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array($e->getMessage()), 500);
        }

        return $app->json(array("Resource updated successful"), 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
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

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function indexAction(Request $request, Application $app)
    {

        $userId = $request->get('userId');

        if (null === $userId) {
            return $app->json(array(), 400);
        }

        /** @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $userId);
        /** @var $model \Model\User\QuestionPaginatedModel */
        $model = $app['users.questions.model'];

        try {
            $result = $paginator->paginate($filters, $model, $request);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
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

            if (empty($data)) {
                return $app->json('The user has not answered to any question', 404);
            }

            return $app->json($data, 200);

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array('error' => 'An error ocurred'), 500);
        }
    }

    public function getAnswerAction(Request $request, Application $app)
    {

        $userId = $request->get('userId');
        $questionId = $request->get('questionId');

        try {
            /** @var AnswerModel $model */
            $model = $app['users.answers.model'];

            $result = $model->getUserAnswer($userId, $questionId);

            $data = array();

            foreach ($result as $row) {
                $data['question']['id'] = $row['question']->getId();
                $data['question']['text'] = $row['question']->getProperty('text');
                foreach ($row['answers'] as $answer) {
                    $data['question']['answers'][] = array(
                        'id' => $answer->getId(),
                        'text' => $answer->getProperty('text'),
                    );
                }

                $data['answer']['answerId'] = $row['answer']->getId();
                $data['answer']['explanation'] = $row['userAnswer']->getProperty('explanation');
                $data['answer']['answeredAt'] = $row['userAnswer']->getProperty('answeredAt');
                $data['answer']['isPrivate'] = $row['userAnswer']->getProperty('private');
                foreach ($row['accepts'] as $acceptedAnswer) {
                    $data['answer']['acceptedAnswers'][] = $acceptedAnswer->getId();
                }
            }

            if (empty($data)) {
                return $app->json(array('error' => 'The user has not answered to any question'), 404);
            }

            return $app->json($data, 200);

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array('error' => 'An error ocurred'), 500);
        }
    }
}
