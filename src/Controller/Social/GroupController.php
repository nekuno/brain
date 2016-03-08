<?php

namespace Controller\Social;

use Model\User;
use Silex\Application;

/**
 * Class GroupController
 * @package Controller
 */
class GroupController
{
    /**
     * @param Application $app
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getAction(Application $app, $id)
    {
        $group = $app['users.groups.model']->getById($id);

        return $app->json($group);
    }
}