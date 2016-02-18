<?php

namespace Controller\User;

use Event\AnswerEvent;
use Model\Questionnaire\QuestionModel;
use Model\User\AnswerModel;
use Model\User\QuestionPaginatedModel;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnswerController
{

    /**
     * @var AnswerModel $answerModel
     */
    protected $answerModel;

    public function __construct(AnswerModel $am)
    {
        $this->answerModel = $am;
    }

    public function answerAction(Request $request, Application $app)
    {

        $data = $request->request->all();
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        $userAnswer = $this->answerModel->answer($data);

        // TODO: Refactor this to listener
        /* @var $questionModel QuestionModel */
        $questionModel = $app['questionnaire.questions.model'];
        $questionModel->setOrUpdateRankingForQuestion($data['questionId']);

        return $app->json($userAnswer);
    }

    public function updateAction(Request $request, Application $app)
    {

        $data = $request->request->all();
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        $userAnswer = $this->answerModel->update($data);

        // TODO: Refactor this to listener
        /* @var $questionModel QuestionModel */
        $questionModel = $app['questionnaire.questions.model'];
        $questionModel->setOrUpdateRankingForQuestion($data['questionId']);

        return $app->json($userAnswer);
    }

    public function validateAction(Request $request, Application $app)
    {

        $data = $request->request->all();
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        $this->answerModel->validate($data, false);

        return $app->json(array(), 200);
    }

    public function explainAction(Request $request, Application $app)
    {

        $data = $request->request->all();
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        $userAnswer = $this->answerModel->explain($data);

        return $app->json($userAnswer);
    }

    public function indexAction(Request $request, Application $app)
    {
        $id = $request->request->get('userId');
        $locale = $this->getLocale($request, $app['locale.options']['default']);

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id, 'locale' => $locale);
        /* @var $model QuestionPaginatedModel */
        $model = $app['users.questions.model'];

        $result = $paginator->paginate($filters, $model, $request);

        return $app->json($result);
    }

    public function countAction(Request $request, Application $app)
    {
        $userId = $request->request->get('userId');

        $userAnswerResult = $this->answerModel->getNumberOfUserAnswers($userId);

        $data = array(
            'userId' => $userId,
        );

        foreach ($userAnswerResult as $row) {
            $data['nOfAnswers'] = $row['nOfAnswers'];
        }

        if (empty($data)) {
            return $app->json('The user has not answered to any question', 404);
        }

        return $app->json($data);

    }

    public function getAnswerAction(Request $request, Application $app)
    {

        $userId = $request->request->get('userId');
        $questionId = (integer)$request->attributes->get('questionId');
        $locale = $this->getLocale($request, $app['locale.options']['default']);

        $result = $this->answerModel->getUserAnswer($userId, $questionId, $locale);

        return $app->json($result);

    }

    public function deleteAnswerAction(Request $request, Application $app)
    {
        $userId = (integer)$request->request->get('userId');
        $questionId = (integer)$request->attributes->get('questionId');
        $locale = $this->getLocale($request, $app['locale.options']['default']);

        try {
            $userAnswer = $this->answerModel->getUserAnswer($userId, $questionId, $locale);
            $answer = $userAnswer['userAnswer'];
        } catch (NotFoundHttpException $e) {
            return $app->json($e->getMessage(), 404);
        }

        $deletion = $this->answerModel->deleteUserAnswer($userId, $answer);

        if (!$deletion) {
            return $app->json('Answer not deleted', 500);
        }

        /* @var $dispatcher EventDispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->dispatch(\AppEvents::ANSWER_ADDED, new AnswerEvent($userId,$questionId));

        /* @var $questionModel QuestionModel */
        $questionModel = $app['questionnaire.questions.model'];

        try {
            $questionModel->skip($answer['questionId'], $userId);
        } catch (\Exception $e) {
            return $app->json($e->getMessage(), 405);
        }

        return $app->json($answer, 200);

    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getOldUserAnswersCompareAction(Request $request, Application $app)
    {

        $otherUserId = $request->get('id');
        $userId = $request->request->get('userId');
        $locale = $request->query->get('locale');
        $showOnlyCommon = $request->query->get('showOnlyCommon', 0);

        if (null === $otherUserId || null === $userId) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $otherUserId, 'id2' => $userId, 'locale' => $locale, 'showOnlyCommon' => $showOnlyCommon);

        /* @var $model \Model\User\OldQuestionComparePaginatedModel */
        $model = $app['old.users.questions.compare.model'];

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
    public function getUserAnswersCompareAction(Request $request, Application $app)
    {

        $otherUserId = $request->get('id');
        $userId = $request->request->get('userId');
        $locale = $request->query->get('locale');
        $showOnlyCommon = $request->query->get('showOnlyCommon', 0);

        if (null === $otherUserId || null === $userId) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $otherUserId, 'id2' => $userId, 'locale' => $locale, 'showOnlyCommon' => $showOnlyCommon);

        /* @var $model \Model\User\QuestionComparePaginatedModel */
        $model = $app['users.questions.compare.model'];

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

    protected function getLocale(Request $request, $defaultLocale)
    {
        $locale = $request->get('locale', $defaultLocale);
        if (!in_array($locale, array('en', 'es'))) {
            $locale = $defaultLocale;
        }

        return $locale;
    }

}
