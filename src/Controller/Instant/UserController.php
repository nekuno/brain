<?php

namespace Controller\Instant;

use Model\User\Group\GroupModel;
use Manager\UserManager;
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

}
