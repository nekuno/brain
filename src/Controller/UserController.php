<?php

namespace Controller;

use Model\UserModel;
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

            if (!is_int($request->request->get('id'))) {
                return $app->json(array(), 400);
            }
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

    public function getMatchingAction(Request $request, Application $app)
    {

        // Get params
        $id1     = $request->get('id1');
        $id2     = $request->get('id2');
        $basedOn = $request->get('type');

        if (null === $id1 || null === $id2) {
            return $app->json(array(), 400);
        }

        try {
            /** @var UserModel $model */
            $model = $app['users.model'];
            if ($basedOn == 'answers') {
                $result = $model->getMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2);
            }
            if ($basedOn == 'content') {
                $result = $model->getMatchingBetweenTwoUsersBasedOnSharedContent($id1, $id2);
            }

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    public function getUserRecommendationAction(Request $request, Application $app)
    {

        // Get params
        $id      = $request->get('id');
        $basedOn = $request->get('type');

        if (null === $id) {
            return $app->json(array(), 400);
        }

        try {
            /** @var UserModel $model */
            $model = $app['users.model'];
            if ($basedOn == 'answers') {
                // TODO: check that users has one answered question at least
                $result = $model->getUserRecommendationsBasedOnAnswers($id);
            }
            if ($basedOn == 'content') {
                $result = $model->getUserRecommendationsBasedOnSharedContent($id);
            }

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    public function getContentRecommendationAction(Request $request, Application $app)
    {

        // Get params
        $id      = $request->get('id');
        $basedOn = $request->get('type');

        if (null === $id) {
            return $app->json(array(), 400);
        }

        try {

            /** @var UserModel $model */
            $model = $app['users.model'];
            $result = $model->getContentRecommendations($id);

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }
} 
