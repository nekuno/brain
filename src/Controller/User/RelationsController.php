<?php

namespace Controller\User;

use Controller\BaseController;
use Model\User\RelationsModel;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RelationsController extends BaseController
{

    public function indexAction(Application $app, $relation)
    {
        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->getAll($this->getUserId(), $relation);

        return $app->json($result);
    }

    public function getAction(Application $app, $to, $relation = null)
    {
        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        if (!is_null($relation)) {
            $result = $model->get($this->getUserId(), $to, $relation);
        } else {
            $result = array();
            foreach (RelationsModel::getRelations() as $relation) {
                try {
                    $model->get($this->getUserId(), $to, $relation);
                    $result[$relation] = true;
                } catch (NotFoundHttpException $e) {
                    $result[$relation] = false;
                }
            }
        }

        return $app->json($result);
    }

    public function getOtherAction(Application $app, $from, $relation = null)
    {
        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        if (!is_null($relation)) {
            $result = $model->get($from, $this->getUserId(), $relation);
        } else {
            $result = array();
            foreach (RelationsModel::getRelations() as $relation) {
                try {
                    $model->get($from, $this->getUserId(), $relation);
                    $result[$relation] = true;
                } catch (NotFoundHttpException $e) {
                    $result[$relation] = false;
                }
            }
        }

        return $app->json($result);
    }

    public function postAction(Request $request, Application $app, $to, $relation)
    {
        $data = $request->request->all();

        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->create($this->getUserId(), $to, $relation, $data);

        return $app->json($result);
    }

    public function deleteAction(Application $app, $to, $relation)
    {
        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->remove($this->getUserId(), $to, $relation);

        return $app->json($result);
    }
}
