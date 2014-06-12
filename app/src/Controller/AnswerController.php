<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/12/14
 * Time: 7:21 PM
 */

namespace Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class AnswerController
{

    public function addAction(Request $request, Application $app)
    {
        $data = $request->request->all();

        if(array() === $data){
            return $app->json(array(), 400);
        }

        // TODO: Validate received data

        $model  = $app['answers.model'];
        $result = $model->create($data);

        return $app->json($result, !empty($result) ? 201 : 200);
    }

} 