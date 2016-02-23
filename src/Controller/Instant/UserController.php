<?php

namespace Controller\Instant;

use Controller\BaseController;
use Model\User\GroupModel;
use Manager\UserManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserController
 * @package Controller
 */
class UserController extends BaseController
{
    /**
     * @param Application $app
     * @param int $id
     * @return JsonResponse
     */
    public function getAction(Application $app, $id)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $user = $model->getById($id)->jsonSerialize();
        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $user['groups'] = $groupModel->getByUser($id);

        return $app->json($user);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function findAction(Application $app, Request $request)
    {
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $criteria = $request->query->all();
        $user = isset($criteria['id']) ? $model->getById($criteria['id']) : $model->findUserBy($criteria);
        $return = $user->jsonSerialize();

        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $return['groups'] = $groupModel->getByUser($user->getId());

        return $app->json($return);
    }
}
