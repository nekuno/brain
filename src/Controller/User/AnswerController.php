<?php

namespace Controller\User;

use Controller\BaseController;
use Event\AnswerEvent;
use Model\Questionnaire\QuestionModel;
use Model\User\AnswerModel;
use Model\User\QuestionPaginatedModel;
use Model\User;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnswerController extends BaseController
{
    /**
     * @var AnswerModel $answerModel
     */
    protected $answerModel;

    public function __construct(User $user, AnswerModel $am)
    {
        $this->answerModel = $am;
        $this->user = $user;
    }

    public function indexAction(Request $request, Application $app)
    {
        $locale = $this->getLocale($request, $app['locale.options']['default']);

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $this->getUserId(), 'locale' => $locale);
        /* @var $model QuestionPaginatedModel */
        $model = $app['users.questions.model'];

        $result = $paginator->paginate($filters, $model, $request);

        return $app->json($result);
    }

    public function getAnswerAction(Request $request, Application $app)
    {
        $questionId = (integer)$request->attributes->get('questionId');
        $locale = $this->getLocale($request, $app['locale.options']['default']);

        $result = $this->answerModel->getUserAnswer($this->getUserId(), $questionId, $locale);

        return $app->json($result);
    }

    public function answerAction(Request $request, Application $app)
    {
        $data = $request->request->all();
        $data['userId'] = $this->getUserId();
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
        $data['userId'] = $this->getUserId();
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
        $data['userId'] = $this->getUserId();
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        $this->answerModel->validate($data);

        return $app->json(array(), 200);
    }

    public function explainAction(Request $request, Application $app)
    {
        $data = $request->request->all();
        $data['userId'] = $this->getUserId();
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        $userAnswer = $this->answerModel->explain($data);

        return $app->json($userAnswer);
    }

    public function countAction(Application $app)
    {
        $userAnswerResult = $this->answerModel->getNumberOfUserAnswers($this->getUserId());

        $data = array(
            'userId' => $this->getUserId(),
        );

        foreach ($userAnswerResult as $row) {
            $data['nOfAnswers'] = $row['nOfAnswers'];
        }

        if (empty($data)) {
            return $app->json('The user has not answered to any question', 404);
        }

        return $app->json($data);
    }

    public function deleteAnswerAction(Request $request, Application $app)
    {
        $questionId = (integer)$request->attributes->get('questionId');
        $locale = $this->getLocale($request, $app['locale.options']['default']);

        try {
            $userAnswer = $this->answerModel->getUserAnswer($this->getUserId(), $questionId, $locale);
            $answer = $userAnswer['userAnswer'];
        } catch (NotFoundHttpException $e) {
            return $app->json($e->getMessage(), 404);
        }

        $deletion = $this->answerModel->deleteUserAnswer($this->getUserId(), $answer);

        if (!$deletion) {
            return $app->json('Answer not deleted', 500);
        }

        /* @var $dispatcher EventDispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->dispatch(\AppEvents::ANSWER_ADDED, new AnswerEvent($this->getUserId(),$questionId));

        /* @var $questionModel QuestionModel */
        $questionModel = $app['questionnaire.questions.model'];

        try {
            $questionModel->skip($answer['questionId'], $this->getUserId());
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
        $locale = $request->query->get('locale');
        $showOnlyCommon = $request->query->get('showOnlyCommon', 0);

        if (null === $otherUserId || null === $this->getUserId()) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $otherUserId, 'id2' => $this->getUserId(), 'locale' => $locale, 'showOnlyCommon' => $showOnlyCommon);

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
        $locale = $request->query->get('locale');
        $showOnlyCommon = $request->query->get('showOnlyCommon', 0);

        if (null === $otherUserId || null === $this->getUserId()) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $otherUserId, 'id2' => $this->getUserId(), 'locale' => $locale, 'showOnlyCommon' => $showOnlyCommon);

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
