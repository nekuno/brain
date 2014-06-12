<?php
/**
 * Created by PhpStorm.
 * User: zaski
 * Date: 6/11/14
 * Time: 6:38 PM
 */

namespace model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;


class AnswerModel {

    protected $client;

    public function __construct(Client $client){

        $this->client = $client;

    }

    public function answer($userId, array $answer = array() ){

        //Construct the query string
        $query =
            "MATCH
                (user:User {_id: '" . $userId. "'}),
                (answered:Answer {_id: '" . $answer['answerId'] . "'}), ";

        $accepted = $answer['acceptedAnswers'];
        $numAccepted = count($accepted);
        $count = 0;
        foreach($accepted as $acc){
            $query .= "(accepted" . $count . ":Answer {_id: '" . $acc['id'] . "'}), ";
            ++$count;
        }

        $query .=
                "(answered)-[:IS_ANSWER_OF]->(question)
            CREATE
                (user)-[:ANSWERS]->(answered), ";

        for ($count = 0; $count < $numAccepted; ++$count){
            $query .= "(user)-[:ACCEPTS]->(accepted" .$count ."), ";
        }

        $query .= "(user)-[:RATES {_rating: " . $answer['rating'] . "}]->(question);";

        //Create the Neo4j query object
        $neoQuery = new Query(
            $this->client,
            $query
        );

        //Execute query
        $result = $neoQuery->getResultSet();

        $response = array();
        $response['creation'] = "ok";

        return $response;
    }

} 