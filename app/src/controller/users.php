<?php

use Symfony\Component\HttpFoundation\Request;

/**
 * Return users
 */
$app->get(
    '/users',
    function () use ($app) {
        $client = $app['neo4j.client'];

        $queryString = 'MATCH (n:User) RETURN n';

        $query = new Everyman\Neo4j\Cypher\Query($client, $queryString);

        $users = array();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $user[$row['u']->getProperty('username')]['username'] = $row['u']->getProperty('username');
            $user[$row['u']->getProperty('username')]['email'] = $row['u']->getProperty('email');
            $user[$row['u']->getProperty('username')]['qnoow_id'] = $row['u']->getProperty('qnoow_id');
        }

        return $app->json($users, 200);
    }
)->bind('users');

/**
 * Adds new User.
 */
$app->post(
    '/users',
    function (Request $request) use ($app) {

        // Basic data validation
        if (array() !== $request->request->all()) {
            if (
                null == $request->request->get('id')
                || null == $request->request->get('username')
                || null == $request->request->get('email')
            ) {
                return $app->json(array(), 400);
            }
        } else {
            return $app->json(array(), 400);
        }

        // Create and persist the User
        $model = new \model\UserModel($app['neo4j.client']);
        $result = $model->create($request->request->all());

        $user = array();

        foreach ($result as $row) {
            $user[$row['u']->getProperty('username')]['username'] = $row['u']->getProperty('username');
            $user[$row['u']->getProperty('username')]['email'] = $row['u']->getProperty('email');
            $user[$row['u']->getProperty('username')]['qnoow_id'] = $row['u']->getProperty('qnoow_id');
        }

        return $app->json($user, !empty($user) ? 201 : 200);

    }
)->bind('users-add');