<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/12/14
 * Time: 7:15 PM
 */

namespace Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class QuestionController
{

    public function addAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        if (empty($data)) {
            return $app->json(array(), 400);
        }

        // TODO: Validate received data

        try {
            $model  = $app['questions.model'];
            $result = $model->create($data);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);

    }

    public function answerAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        if (array() === $data) {
            return $app->json(array(), 400);
        }

        // TODO: Validate received data

        try {
            $model  = $app['questions.model'];
            $result = $model->answer($data);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, 201);

    }

} 
