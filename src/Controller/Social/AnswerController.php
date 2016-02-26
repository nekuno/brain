<?php

namespace Controller\Social;

use Model\Questionnaire\QuestionModel;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class AnswerController
{
    /**
     * @param Request $request
     * @param Application $app
     * @param integer $id
     * @param mixed $questionId
     * @return JsonResponse
     * @throws \Exception
     */
    public function updateAction(Request $request, Application $app, $id, $questionId)
    {
        $data = $request->request->all();
        $data['userId'] = (int)$id;
        $data['questionId'] = (int)$questionId;
        $data['locale'] = $this->getLocale($request, $app['locale.options']['default']);

        $userAnswer = $app['users.answers.model']->update($data);

        // TODO: Refactor this to listener
        /* @var $questionModel QuestionModel */
        $questionModel = $app['questionnaire.questions.model'];
        $questionModel->setOrUpdateRankingForQuestion($data['questionId']);

        return $app->json($userAnswer);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param integer $id
     * @param integer $id2
     * @return JsonResponse
     * @throws \Exception
     */
    public function getUserAnswersCompareAction(Request $request, Application $app, $id, $id2)
    {
        $locale = $request->query->get('locale');
        $showOnlyCommon = $request->query->get('showOnlyCommon', 0);

        if (null === $id2 || null === $id) {
            return $app->json(array(), 400);
        }

        /* @var $paginator \Paginator\Paginator */
        $paginator = $app['paginator'];

        $filters = array('id' => $id2, 'id2' => $id, 'locale' => $locale, 'showOnlyCommon' => $showOnlyCommon);

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

    protected function getLocale(Request $request, $defaultLocale)
    {
        $locale = $request->get('locale', $defaultLocale);
        if (!in_array($locale, array('en', 'es'))) {
            $locale = $defaultLocale;
        }

        return $locale;
    }
}
