

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
               $request->request->get('id') == null
            ) {
                return $app->json(array(), 400);
            }
        } else {
            return $app->json(array(), 400);
        }


        // Create and persist the User
        $model = new \model\UserModel($app['neo4j.client']);
        $response = $model->create($request->request->all());

        return $app->json($response, !empty($response) ? 201 : 200);

    }
)->bind('users-add');

/**
 * User {id} answers a question
 */
$app->post(
    'users/{id}/answers',
    function(Request $request, $id) use ($app){


        $client = $app['neo4j.client'];
        $model = new \model\AnswerModel($client);
        $data = $request->request->all();
        $response = $model->answer($id, $data);

        return $app->json($response, !empty($response) ? 201 : 200);
    }
)->bind('users-answer-question');

/**
 *  User {id} matches another user
 */
$app->post(
    'users/{id1}/matches',
    function(Request $request, $id1) use ($app){

        $client = $app['neo4j.client'];


        //Get id from the JSON
        $id2 = $request->request->get("id");

        //DEUDA-TECNICA: [IMPORTANT] CHECK THAT BOTH USERS HAVE AT LEAST ONE ANSWERED QUESTION (divide by 0 in the query otherwise)

        $model = new \model\MatchModel($client);
        $data = $request->request->all();
        $response = $model->create($id1, $data);

        //return $query;
        return $app->json($response, !empty($response) ? 201 : 200);
    }
)->bind('users-matches-user');