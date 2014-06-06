<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get(
    '/',
    function () use ($app) {

        return $app['twig']->render('index.html');
    }
)
    ->bind('homepage');

$app->get(
    '/users/load/$username',
    function () use ($app) {
        $client = $app['neo4j.client'];
        $error = array();
        for ($i = 1; $i <= 1; $i++) {
            $username = 'username' . $i;
            $query = new Everyman\Neo4j\Cypher\Query(
                $client,
                'CREATE (u:USER {_username: "' . $username . '", _status: "1"});'
            );

            try {
                $client->executeCypherQuery($query);
            } catch (\Exception $e) {
                $error[] = sprintf('Error al añadir el usuario %s', $i);
            }
        }

        if (!empty($error)) {
            return var_dump($error);
        }

        return 'Complete!';
    }
)
    ->bind('load_users');

$app->get(
    '/users/remove',
    function () use ($app) {
        $client = $app['neo4j.client'];
        $error = array();

        $query = new Everyman\Neo4j\Cypher\Query(
            $client,
            'MATCH (n) OPTIONAL MATCH (n)-[r]-() DELETE n,r'
        );

        try {
            $client->executeCypherQuery($query);
        } catch (\Exception $e) {
            $error[] = $e->getMessage();
        }

        if (!empty($error)) {
            return var_dump($error);
        }

        return 'Complete!';
    }
)
    ->bind('remove_users');

$app->get(
    '/users/view',
    function () use ($app) {
        $client = $app['neo4j.client'];

        $queryString = 'MATCH n RETURN n LIMIT 25';

        $query = new Everyman\Neo4j\Cypher\Query($client, $queryString);

        $result = $query->getResultSet();

        foreach ($result as $row) {
            $output[] = $row['n']->getProperty('_username');
        }

        return new Response(var_dump($output));
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

        // Parse request content and populate parameters
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());

        $query = new Everyman\Neo4j\Cypher\Query(
            $neo4j,
            "CREATE (u:User { status: 1,
                qnoow_id: " . $request->request->get('id') . ",
                username: '" . $request->request->get('username') . "',
                email: '" . $request->request->get('email') . "'
            }) RETURN u;"
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

$app->error(
    function (\Exception $e, $code) use ($app) {
        if ($app['debug']) {
            return;
        }

        // 404.html, or 40x.html, or 4xx.html, or error.html
        $templates = array(
            'errors/' . $code . '.html',
            'errors/' . substr($code, 0, 2) . 'x.html',
            'errors/' . substr($code, 0, 1) . 'xx.html',
            'errors/default.html',
        );

        return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
    }

);
