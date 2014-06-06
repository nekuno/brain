<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
            $users[]['username'] = $row['n']->getProperty('username');
            $users[]['email'] = $row['n']->getProperty('email');
            $users[]['qnoow_id'] = $row['n']->getProperty('qnoow_id');
        }

        return $app->json($users, 200);
    }
)
    ->bind('view_users');

/**
 * Adds new User.
 */
$app->post(
    '/users',
    function (Request $request) use ($app) {

        $neo4j = $app['neo4j.client'];

        if($request->request->all() === array()){
            return $app->json(array(), 500);
        }

        $query = new Everyman\Neo4j\Cypher\Query(
            $neo4j,
            "CREATE (u:User { status: 1, qnoow_id: '" . $request->request->get('id') . "', username: '" . $request->request->get('username') . "', email: '" . $request->request->get('email') . "'})  RETURN u;"
        );

        // Execute query and get new created user
        $result = $query->getResultSet();

        $user = array();

        foreach ($result as $row) {
            $user['username'] = $row['u']->getProperty('username');
            $user['email'] = $row['u']->getProperty('email');
            $user['qnoow_id'] = $row['u']->getProperty('qnoow_id');
        }

        if (!empty($user)) {
            $code = 201;

        } else {
            $code = 200;
        }

        return $app->json($user, $code);

    }
)->bind('users-add');