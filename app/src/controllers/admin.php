<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->get(
    '/users/load',
    function () use ($app) {
        $client = $app['neo4j.client'];
        $error = array();
        for ($i = 1; $i <= 100; $i++) {
            $username = 'username' . $i;
            $query = new Everyman\Neo4j\Cypher\Query(
                $client,
                "CREATE (u:User {qnoow_id: " . $i . ", username: '" . $username . "', email: 'example@example.com', status: 1 }) RETURN u"
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
            'MATCH (n) OPTIONAL MATCH (n)-[r]-() DELETE r,n'
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

