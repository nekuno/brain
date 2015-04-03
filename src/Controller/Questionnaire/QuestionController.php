<?php

namespace Controller\Questionnaire;

use Model\Questionnaire\QuestionModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class QuestionController
{

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function getQuestionsAction(Request $request, Application $app)
    {

        $locale = $this->getLocale($request, $app['locale.options']['default']);
        $skip = $request->query->get('skip');
        $limit = $request->query->get('limit', 10);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        $questions = $model->getAll($locale, $skip, $limit);

        return $app->json($questions);

    }

    /**
     * Returns an unanswered question for given user
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function getNextQuestionAction(Request $request, Application $app)
    {

        $userId = $request->query->get('userId');

        $locale = $this->getLocale($request, $app['locale.options']['default']);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        $question = $model->getNextByUser($userId, $locale);

        return $app->json($question);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function getQuestionAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        $locale = $this->getLocale($request, $app['locale.options']['default']);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        $question = $model->getById($id, $locale);

        return $app->json($question);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function postQuestionAction(Request $request, Application $app)
    {

        /* @var $model QuestionModel */
        $model = $app['questionnaire.questions.model'];

        $question = $model->create($request->request->all());

        return $app->json($question, 201);

    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function skipAction(Request $request, Application $app)
    {

        $id = $request->attributes->get('id');
        $userId = $request->request->get('userId');

        $locale = $this->getLocale($request, $app['locale.options']['default']);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        $question = $model->getById($id, $locale);

        $model->skip($id, $userId);

        return $app->json($question);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function reportAction(Request $request, Application $app)
    {

        $id = $request->attributes->get('id');
        $userId = $request->request->get('userId');
        $reason = $request->request->get('reason');

        $locale = $this->getLocale($request, $app['locale.options']['default']);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        $question = $model->getById($id, $locale);

        $model->report($id, $userId, $reason);

        return $app->json($question);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function statsAction(Request $request, Application $app)
    {

        $id = $request->attributes->get('id');
        $locale = $this->getLocale($request, $app['locale.options']['default']);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        $question = $model->getById($id, $locale);

        $stats = $model->getQuestionStats($id);

        return $app->json($stats);
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