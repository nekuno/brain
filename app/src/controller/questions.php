<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$app->post(
    '/questions',
    function(Request $request) use($app) {
        $neo4j = $app['neo4j.client'];

        //Parse request content
        $data = json_decode($request -> getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());

        //Construct the query string
        $query = "CREATE (q:QUESTION {_id: '" . $request->request->get("id") . "', _content: '". $request->request->get("content") . "'}), ";

        $answers = $request->request->get("answers");
        $numAnswers = count($answers);
        $count = 0;
        foreach($answers as $answer){
            $query .= "(:ANSWER {_id: '" . $answer['id'] . "', _content: '" . $answer['content'] . "'})-[:IS_ANSWER_OF]->(q)";
            if(++$count === $numAnswers){
                $query .= ";";
            }
            else{
                $query .= ", ";
            }
        }

        //Create the Neo4j query object
        $neoQuery = new Everyman\Neo4j\Cypher\Query(
            $neo4j,
            $query
        );

        //Execute query and get new created user
        try{
            $result = $neoQuery->getResultSet();
        } catch(\Exception $e){
            return $app->json(array(), 400);
        }
        //TODO: implement a decent result


        return "Question with answers created correctly";

    }
)->bind('add_question');