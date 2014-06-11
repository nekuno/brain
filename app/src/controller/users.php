

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

        $neo4j = $app['neo4j.client'];

        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());

        //Get id from the JSON
        $id2 = $request->request->get("id");

        //DEUDA-TECNICA: [IMPORTANT] CHECK THAT BOTH USERS HAVE AT LEAST ONE ANSWERED QUESTION (divide by 0 in the query otherwise)

        //Construct the query string
        $query = "MATCH (u1:USER {_username: '" . $id1 . "'}), (u2:USER {_username: '" . $id2 . "'}) MATCH (u1)-[:ACCEPTS]->(commonanswer1:ANSWER)<-[:ANSWERS]-(u2), (commonanswer1)-[:IS_ANSWER_OF]->(commonquestion1)<-[r1:RATES]-(u1) MATCH (u2)-[:ACCEPTS]->(commonanswer2:ANSWER)<-[:ANSWERS]-(u1), (commonanswer2)-[:IS_ANSWER_OF]->(commonquestion2)<-[r2:RATES]-(u2) MATCH (u1)-[:ANSWERS]->(:ANSWER)-[:IS_ANSWER_OF]->(commonquestion:QUESTION)<-[:IS_ANSWER_OF]-(:ANSWER)<-[:ANSWERS]-(u2), (u1)-[r3:RATES]->(commonquestion)<-[r4:RATES]-(u2) WITH [n1 IN collect(distinct r1) |n1._rating] AS little1_elems, [n2 IN collect(distinct r2) |n2._rating] AS little2_elems, [n3 IN collect(distinct r3) |n3._rating] AS CIT1_elems, [n4 IN collect(distinct r4) |n4._rating] AS CIT2_elems WITH reduce(little1 = 0, n1 IN little1_elems | little1 + n1) AS little1, reduce(little2 = 0, n2 IN little2_elems | little2 + n2) AS little2, reduce(CIT1 = 0, n3 IN CIT1_elems | CIT1 + n3) AS CIT1, reduce(CIT1 = 0, n4 IN CIT2_elems | CIT1 + n4) AS CIT2 WITH sqrt( (little1*1.0/CIT1) * (little2*1.0/CIT2) ) AS match_user1_user2 MATCH (u1:USER {_username: '" . $id1 . "'}), (u2:USER {_username: '" . $id2 . "'}) CREATE UNIQUE (u1)-[m:MATCHES]-(u2) SET m._matching = match_user1_user2;";

        //Create the Neo4j query object
        $neoQuery = new Everyman\Neo4j\Cypher\Query(
            $neo4j,
            $query
        );

        //Execute query
        $result = $neoQuery->getResultSet();

        //DEUDA-TECNICA: implement a decent result to return

        //return $query;
        return "Set that user " . $id1 . " matches " . $id2;
    }
)->bind('users-matches-user');