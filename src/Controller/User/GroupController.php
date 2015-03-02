<?php

namespace Controller\User;

use Model\User\GroupModel;
use Model\UserModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupController
 * @package Controller
 */
class GroupController
{

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */

     public function addAction(Request $request, Application $app)
    {

        // Basic data validation
        if (array() !== $request->request->all()) {
            if (null == $request->request->get('groupId') || null == $request->request->get('groupName')
            ) {
                return $app->json(array(), 400);
            }

            if (!is_int($request->request->get('groupId'))) {
                return $app->json(array(), 400);
            }
        } else {
            return $app->json(array(), 400);
        }

        // Create and persist the Group

        try {
            $model = $app['users.groups.model'];
            $result = $model->create($request->request->all());
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
     public function showAction(Request $request, Application $app)
    {

        $id = $request->get('groupId');
        if (null === $id) {
            return $app->json(array(), 404);
        }

        try {
            $model = $app['users.groups.model'];
            $result = $model->getById($request->get('groupId'));
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 200 : 404);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function deleteAction(Request $request, Application $app)
    {

        $id = $request->get('groupId');
        if (null === $id) {
            return $app->json(array(), 400);
        }

        try {
            $model = $app['users.groups.model'];
            $model->remove($id);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json(array(), 200);
    }

    /**
     * Creates a "Belonging to" relationship between user and group
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function addUserAction(Request $request, Application $app)
    {

        // Basic data validation
        if (array() !== $request->request->all()) {
            if (null == $request->request->get('groupId') || null == $request->request->get('userId')
            ) {
                return $app->json(array(), 400);
            }

            if (!is_int($request->request->get('groupId'))||!is_int($request->request->get('userId'))) {
                return $app->json(array(), 400);
            }
        } else {
            return $app->json(array(), 400);
        }

        try {
            $model = $app['users.groups.model'];
            $model->addUserToGroup($request->request->all());
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json(array(), 200);
    }

    /**
     * Removes a "Belonging to" relationship between user and group
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function removeUserAction(Request $request, Application $app)
    {

        // Basic data validation
        if (array() !== $request->request->all()) {
            if (null == $request->request->get('groupId') || null == $request->request->get('userId')
            ) {
                return $app->json(array(), 400);
            }

            if (!is_int($request->request->get('groupId'))||!is_int($request->request->get('userId'))) {
                return $app->json(array(), 400);
            }
        } else {
            return $app->json(array(), 400);
        }

        try {
            $model = $app['users.groups.model'];
            $model->removeUserFromGroup($request->request->all());
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json(array(), 200);
    }

}