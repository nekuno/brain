<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 1/10/14
 * Time: 16:40
 */

namespace Controller\User;

use Model\Questionnaire\AnswerModel;
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

        $answerId = (integer) $request->get('answerId');
        $data = $request->request->all();
        $data['currentId'] = $answerId;

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

        $data = array(
            'answerId' => (integer) $request->get('answerId'),
            'userId' => (integer) $request->get('userId'),
            'explanation' => $request->request->get('explanation'),
        );

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
}