<?php

namespace Controller\User;

use Model\User\RelationsModel;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

class RelationsController
{
    /**
     * @param Application $app
     * @param User $user
     * @param string $relation
     * @return JsonResponse
     */
    public function indexAction(Application $app, User $user, $relation)
    {
        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->getAll($user->getId(), $relation);

        return $app->json($result);
    }

    /**
     * @param Application $app
     * @param User $user
     * @param integer $to
     * @param string $relation
     * @return JsonResponse
     */
    public function getAction(Application $app, User $user, $to, $relation = null)
    {
        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        if (!is_null($relation)) {
            $result = $model->get($user->getId(), $to, $relation);
        } else {
            $result = array();
            foreach (RelationsModel::getRelations() as $relation) {
                try {
                    $model->get($user->getId(), $to, $relation);
                    $result[$relation] = true;
                } catch (NotFoundHttpException $e) {
                    $result[$relation] = false;
                }
            }
        }

        return $app->json($result);
    }

    /**
     * @param Application $app
     * @param User $user
     * @param integer $from
     * @param string $relation
     * @return JsonResponse
     */
    public function getOtherAction(Application $app, User $user, $from, $relation = null)
    {
        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        if (!is_null($relation)) {
            $result = $model->get($from, $user->getId(), $relation);
        } else {
            $result = array();
            foreach (RelationsModel::getRelations() as $relation) {
                try {
                    $model->get($from, $user->getId(), $relation);
                    $result[$relation] = true;
                } catch (NotFoundHttpException $e) {
                    $result[$relation] = false;
                }
            }
        }

        return $app->json($result);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @param integer $to
     * @param string $relation
     * @return JsonResponse
     */
    public function postAction(Request $request, Application $app, User $user, $to, $relation)
    {
        $data = $request->request->all();

        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->create($user->getId(), $to, $relation, $data);

        return $app->json($result);
    }

    /**
     * @param Application $app
     * @param User $user
     * @param integer $to
     * @param string $relation
     * @return JsonResponse
     */
    public function deleteAction(Application $app, User $user, $to, $relation)
    {
        /* @var $model RelationsModel */
        $model = $app['users.relations.model'];

        $result = $model->remove($user->getId(), $to, $relation);

        return $app->json($result);
    }
}
