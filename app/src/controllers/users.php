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

//        $app['monolog']->addDebug($request->getContent());
        // Parse request content and populate parameters
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());

        $query = new Everyman\Neo4j\Cypher\Query(
            $neo4j,
            "CREATE (u:USER {_status: 'active', _id: '" . $request->request->get('id') . "'})  RETURN u;"
        );

        // Execute query and get new created user
        $result = $query->getResultSet();

        $user = array();

        foreach ($result as $row) {
            $user['status'] = $row['u']->getProperty('_status');
            $user['id'] = $row['u']->getProperty('_id');
        }

        if (!empty($user)) {
            $code = 201;

        } else {
            $code = 200;
        }

        return $app->json($user, $code);

    }
)->bind('users-add');

/**
 * User {id} answers a question
 */
$app->post(
    'users/{id}/answers',
    function(Request $request, $id) use ($app){

        $neo4j = $app['neo4j.client'];

        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());

        //Construct the query string
        $query = "MATCH (user:USER {_id: '" . $id . "'}), (answered:ANSWER {_id: '" . $request->request->get("id") . "'}), ";

        $accepted = $request->request->get("accepted");
        $numAccepted = count($accepted);
        $count = 0;
        foreach($accepted as $acc){
            $query .= "(accepted" . $count . ":ANSWER {_id: '" . $acc['id'] . "'}), ";
            ++$count;
        }

        $query .= "(answered)-[:IS_ANSWER_OF]->(question) CREATE (user)-[:ANSWERS]->(answered), ";

        for ($count = 0; $count < $numAccepted; ++$count){
            $query .= "(user)-[:ACCEPTS]->(accepted" .$count ."), ";
        }

        $query .= "(user)-[:RATES {_rating: " . $request->request->get("rating") . "}]->(question);";

        //Create the Neo4j query object
        $neoQuery = new Everyman\Neo4j\Cypher\Query(
            $neo4j,
            $query
        );

        //Execute query
        $result = $neoQuery->getResultSet();

        //TODO: implement a decent result to return

        //return $query;
       return "Set that user " . $id . " answers question" . $request->request->get("id");
    }
)->bind('users-answer-question');

/**
 *  User {id} matches another user
 */
$app->post(
    'users/{id1}/answers',
    function(Request $request, $id1) use ($app){

        $neo4j = $app['neo4j.client'];

        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());

        //Get id from the JSON
        $id2 = $request->request->get("id");

        //TODO: [IMPORTANT] CHECK THAT BOTH USERS HAVE AT LEAST ONE ANSWERED QUESTION (divide by 0 in the query otherwise)

        //Construct the query string
        $query = "MATCH (u1:USER {_username: '" . $id1 . "'}), (u2:USER {_username: '" . $id2 . "'}) MATCH (u1)-[:ACCEPTS]->(commonanswer1:ANSWER)<-[:ANSWERS]-(u2), (commonanswer1)-[:IS_ANSWER_OF]->(commonquestion1)<-[r1:RATES]-(u1) MATCH (u2)-[:ACCEPTS]->(commonanswer2:ANSWER)<-[:ANSWERS]-(u1), (commonanswer2)-[:IS_ANSWER_OF]->(commonquestion2)<-[r2:RATES]-(u2) MATCH (u1)-[:ANSWERS]->(:ANSWER)-[:IS_ANSWER_OF]->(commonquestion:QUESTION)<-[:IS_ANSWER_OF]-(:ANSWER)<-[:ANSWERS]-(u2), (u1)-[r3:RATES]->(commonquestion)<-[r4:RATES]-(u2) WITH [n1 IN collect(distinct r1) |n1._rating] AS little1_elems, [n2 IN collect(distinct r2) |n2._rating] AS little2_elems, [n3 IN collect(distinct r3) |n3._rating] AS CIT1_elems, [n4 IN collect(distinct r4) |n4._rating] AS CIT2_elems WITH reduce(little1 = 0, n1 IN little1_elems | little1 + n1) AS little1, reduce(little2 = 0, n2 IN little2_elems | little2 + n2) AS little2, reduce(CIT1 = 0, n3 IN CIT1_elems | CIT1 + n3) AS CIT1, reduce(CIT1 = 0, n4 IN CIT2_elems | CIT1 + n4) AS CIT2 WITH sqrt( (little1*1.0/CIT1) * (little2*1.0/CIT2) ) AS match_user1_user2 MATCH (u1:USER {_username: '" . $id1 . "'}), (u2:USER {_username: '" . $id2 . "'}) CREATE UNIQUE (u1)-[m:MATCHES]-(u2) SET m._matching = match_user1_user2;";

        //Create the Neo4j query object
        $neoQuery = new Everyman\Neo4j\Cypher\Query(
            $neo4j,
            $query
        );

        //Execute query
        $result = $neoQuery->getResultSet();

        //TODO: implement a decent result to return

        //return $query;
        return "Set that user " . $id1 . " matches " . $id2;
    }
)->bind('users-matches-user');