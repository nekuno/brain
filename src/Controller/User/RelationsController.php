<?php

namespace Controller\User;

use Model\User\RelationsModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RelationsController
{

    public function indexAction(Application $app, $from, $relation)
    {

        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->getAll($from, $relation);

        return $app->json($result);
    }

    public function getAction(Application $app, $from, $to, $relation = null)
    {
        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        if (!is_null($relation)) {

            $result = $model->get($from, $to, $relation);

        } else {

            $result = array();
            foreach (RelationsModel::getRelations() as $relation) {
                try {
                    $model->get($from, $to, $relation);
                    $result[$relation] = true;
                } catch (NotFoundHttpException $e) {
                    $result[$relation] = false;
                }
            }
        }

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

    public function contactFromAction(Application $app, $id)
    {

        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $users = $model->contactFrom($id);

        return $app->json($users);
    }

    public function contactToAction(Application $app, $id)
    {

        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $users = $model->contactTo($id);

        return $app->json($users);
    }

    public function contactAction(Application $app, $from, $to)
    {

        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $contact = $model->contact($from, $to);

        return $app->json(array(), $contact ? 200 : 404);

    }
}
