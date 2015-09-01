<?php

namespace Controller\User;

use Model\User\RelationsModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class RelationsController
{

    public function indexAction(Application $app, $from, $relation)
    {

        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->getAll($from, $relation);

        return $app->json($result);
    }

    public function getAction(Application $app, $from, $to, $relation)
    {
        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->get($from, $to, $relation);

        return $app->json($result);
    }

    public function postAction(Request $request, Application $app, $from, $to, $relation)
    {

        $data = $request->request->all();

        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->create($from, $to, $relation, $data);

        return $app->json($result);
    }

    public function deleteAction(Application $app, $from, $to, $relation)
    {

        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->remove($from, $to, $relation);

        return $app->json($result);
    }
}
