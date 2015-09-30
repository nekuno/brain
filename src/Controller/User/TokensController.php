<?php

namespace Controller\User;

use Model\User\TokensModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class TokensController
 * @package Controller
 */
class TokensController
{

    /**
     * @param Application $app
     * @param int $id
     * @return JsonResponse
     */
    public function getAllAction(Application $app, $id)
    {

        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $tokens = $model->getAll($id);

        return $app->json($tokens);
    }

    /**
     * @param Application $app
     * @param int $id
     * @param string $resourceOwner
     * @return JsonResponse
     */
    public function getAction(Application $app, $id, $resourceOwner)
    {

        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->getById($id, $resourceOwner);

        return $app->json($token);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param int $id
     * @param string $resourceOwner
     * @return JsonResponse
     */
    public function postAction(Request $request, Application $app, $id, $resourceOwner)
    {

        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->create($id, $resourceOwner, $request->request->all());

        return $app->json($token, 201);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param int $id
     * @param string $resourceOwner
     * @return JsonResponse
     */
    public function putAction(Request $request, Application $app, $id, $resourceOwner)
    {

        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->update($id, $resourceOwner, $request->request->all());

        return $app->json($token);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param int $id
     * @param string $resourceOwner
     * @return JsonResponse
     */
    public function deleteAction(Application $app, $id, $resourceOwner)
    {

        /* @var $model TokensModel */
        $model = $app['users.tokens.model'];

        $token = $model->remove($id, $resourceOwner);

        return $app->json($token);
    }

}
