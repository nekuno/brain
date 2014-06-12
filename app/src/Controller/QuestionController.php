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

class QuestionController {

    public function indesAction(Request $request, Application $app){

    }

    public function addAction(Request $request, Application $app)
    {

        $model = $app['questions.model'];

        $data = $request->request->all();

        $response = $model->create($data);

        return $app->json($response, !empty($response) ? 201 : 200);

    }

    public function deleteAction(Request $request, Application $app)
    {

    }

} 