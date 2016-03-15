<?php

namespace Controller\Social;

use Model\User\GroupModel;
use Manager\UserManager;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserController
 * @package Controller
 */
class UserController
{
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
        $userArray = $user->jsonSerialize();

        /* @var $groupModel GroupModel */
        $groupModel = $app['users.groups.model'];
        $userArray['groups'] = $groupModel->getByUser($user->getId());

        return $app->json($userArray);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param integer $id
     * @return JsonResponse
     */
    public function putAction(Application $app, Request $request, $id)
    {
        $data = $request->request->all();
        $data['userId'] = (int)$id;
        /* @var $model UserManager */
        $model = $app['users.manager'];
        $user = $model->update($data);

        return $app->json($user);
    }

    public function jwtAction(Application $app, $id)
    {

        $authService = $app['auth.service'];
        $jwt = $authService->getToken($id);

        return $app->json(array('jwt' => $jwt));

    }
}
