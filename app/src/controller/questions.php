<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$app->post(
    '/questions',
    function(Request $request) use($app) {

        $client = $app['neo4j.client'];
        $model = new \model\QuestionModel($client);
        $data = $request->request->all();
        $response = $model->create($data);

        return $app->json($response, !empty($response) ? 201 : 200);

    }
)->bind('add_question');