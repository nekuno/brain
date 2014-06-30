<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/9/14
 * Time: 3:12 PM
 */

namespace Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class UserController
{

    public function indexAction(Request $request, Application $app)
    {

        try {
            $model  = $app['users.model'];
            $result = $model->getAll();
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }
            return $app->json(array(), 500);
        }

        return $app->json($result, 200);
    }

    public function addAction(Request $request, Application $app)
    {

        // Basic data validation
        if (array() !== $request->request->all()) {
            if (
                null == $request->request->get('id')
                || null == $request->request->get('username')
                || null == $request->request->get('email')
            ) {
                return $app->json(array(), 400);
            }

            if (!is_int($request->request->get('id')))
                return $app->json(array(), 400);
        } else {
            return $app->json(array(), 400);
        }

        // Create and persist the User

        try {
            $model  = $app['users.model'];
            $result = $model->create($request->request->all());
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }
            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    public function showAction(Request $request, Application $app)
    {

        $id = $request->get('id');
        if (null === $id) {
            return $app->json(array(), 404);
        }



        try {
            $model  = $app['users.model'];
            $result = $model->getById($request->get('id'));
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }
            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($user) ? 200 : 404);
    }

    public function deleteAction(Request $request, Application $app)
    {

        $id = $request->get('id');

        if (null === $id) {
            return $app->json(array(), 400);
        }



        try {
            $model = $app['users.model'];
            $model->remove($id);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }
            return $app->json(array(), 500);
        }

        return $app->json(array(), 200);

    }

    public function getMatchingByQuestionsAction(Request $request, Application $app)
    {

        $id1 = $request->query->get('id1');
        $id2 = $request->query->get('id2');

        if (null === $id1 || null === $id2) {
            return $app->json(array(), 400);
        }

        // TODO: check that users has one answered question at least

        try {
            $model  = $app['users.model'];
            $result = $model->getMatchingByQuestionsByIds($id1, $id2);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }
            return $app->json(array(), 500);
        }

        //return $query;
        return $app->json($result, !empty($result) ? 201 : 200);
    }

    public function getMatchingByContentAction(Request $request, Application $app)
    {

        $id1 = $request->query->get('id1');
        $id2 = $request->query->get('id2');

        if (null === $id1 || null === $id2) {
            return $app->json(array(), 400);
        }

        // TODO: check that users has one answered question at least

        try {
            $model  = $app['users.model'];
            $result = $model->getMatchingByContentByIds($id1, $id2);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }
            return $app->json(array(), 500);
        }

        //return $query;
        return $app->json($result, !empty($result) ? 201 : 200);
    }

} 