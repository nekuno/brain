<?php

namespace Controller\Social;

use Model\Questionnaire\QuestionModel;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class QuestionController
{
    /**
     * Returns an unanswered question for given user
     * @param Request $request
     * @param Application $app
     * @param integer $id
     * @return JsonResponse
     */
    public function getNextQuestionAction(Request $request, Application $app, $id)
    {
        $locale = $this->getLocale($request, $app['locale.options']['default']);
        /* @var QuestionModel $model */
        $model = $app['questionnaire.questions.model'];

        $question = $model->getNextByUser($id, $locale);

        return $app->json($question);
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