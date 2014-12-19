<?php

namespace Controller\Questionnaire;

use Model\Exception\ValidationException;
use Model\Questionnaire\QuestionModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $locale = $this->getLocale($request, $app['locale.options']['default']);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        try {
            $question = $model->getNextByUser($userId, $locale);
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        }

        return $app->json($question, 200);
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

        $questions = $model->getAll($locale, $limit);

        return $app->json($questions, 200);

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

        try {
            $question = $model->getById($id, $locale);
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        }

        return $app->json($question, 200);
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

        try {
            $question = $model->create($request->request->all());
        } catch (ValidationException $e) {
            return $app->json(array('validationErrors' => $e->getErrors()), 500);
        }

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

        $id = $request->get('id');
        $locale = $this->getLocale($request, $app['locale.options']['default']);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        try {
            $question = $model->getById($id, $locale);
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        }

        try {
            $model->skip($id, $request->request->get('userId'));
        } catch (\Exception $e) {
            return $app->json(array('error' => $e->getMessage()), 500);
        }

        return $app->json($question, 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws \Exception
     */
    public function reportAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        $locale = $this->getLocale($request, $app['locale.options']['default']);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        try {
            $question = $model->getById($id, $locale);
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        }

        try {
            $model->report($id, $request->request->get('userId'), $request->request->get('reason'));
        } catch (\Exception $e) {
            return $app->json(array('error' => $e->getMessage()), 500);
        }

        return $app->json($question, 200);
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
        $locale = $this->getLocale($request, $app['locale.options']['default']);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        try {
            $question = $model->getById($id, $locale);
        } catch (HttpException $e) {
            return $app->json(array('error' => $e->getMessage()), $e->getStatusCode(), $e->getHeaders());
        }

        try {
            $stats = $model->getQuestionStats($id);
        } catch (\Exception $e) {
            return $app->json(array('error' => 'Error retrieving stats'), 500);
        }

        return $app->json($stats, 200);
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