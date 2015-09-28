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

    public function createAction(Request $request, Application $app)
    {

        $data = $request->request->all();
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        $userAnswer = $this->answerModel->create($data);

        // TODO: Refactor this to listener
        /* @var $questionModel QuestionModel */
        $questionModel = $app['questionnaire.questions.model'];
        $questionModel->setOrUpdateRankingForQuestion($data['questionId']);

        return $app->json($userAnswer, 201);
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
        $data['userId'] = (integer)$request->attributes->get('userId');
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        $userAnswer = $this->answerModel->explain($data);

        return $app->json($userAnswer);
    }

    public function indexAction(Request $request, Application $app)
    {
        // TODO: Refactor this
        $id = $request->get('userId');
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
        // TODO: Refactor this
        $userId = $request->get('userId');

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

        $userId = (integer)$request->attributes->get('userId');
        $questionId = (integer)$request->attributes->get('questionId');
        $locale = $this->getLocale($request, $app['locale.options']['default']);

        $result = $this->answerModel->getUserAnswer($userId, $questionId, $locale);

        return $app->json($result);

    }

    public function deleteAnswerAction(Request $request, Application $app)
    {
        $userId = (integer)$request->attributes->get('userId');
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

    protected function getLocale(Request $request, $defaultLocale)
    {
        $locale = $request->get('locale', $defaultLocale);
        if (!in_array($locale, array('en', 'es'))) {
            $locale = $defaultLocale;
        }

        return $locale;
    }

}
