<?php

namespace Controller\User;

use Model\Questionnaire\QuestionModel;
use Model\User\AnswerModel;
use Model\User\QuestionPaginatedModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class AnswerController
{

    public function createAction(Request $request, Application $app)
    {

        $data = $request->request->all();
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        /* @var $model AnswerModel */
        $model = $app['users.answers.model'];

        $userAnswer = $model->create($data);

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

        /* @var $model AnswerModel */
        $model = $app['users.answers.model'];

        $userAnswer = $model->update($data);

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

        /* @var $model AnswerModel */
        $model = $app['users.answers.model'];
        $model->validate($data, false);

        return $app->json(array(), 200);
    }

    public function explainAction(Request $request, Application $app)
    {

        $data = $request->request->all();
        $data['userId'] = (integer)$request->attributes->get('userId');
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        /* @var $model AnswerModel */
        $model = $app['users.answers.model'];
        $userAnswer = $model->explain($data);

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

        /* @var AnswerModel $model */
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

        return $app->json($data);

    }

    public function getAnswerAction(Request $request, Application $app)
    {

        $userId = (integer)$request->attributes->get('userId');
        $questionId = (integer)$request->attributes->get('questionId');
        $locale = $this->getLocale($request, $app['locale.options']['default']);

        /* @var $model AnswerModel */
        $model = $app['users.answers.model'];

        $result = $model->getUserAnswer($userId, $questionId, $locale);

        return $app->json($result);

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
