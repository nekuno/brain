<?php
/**
 * Created by PhpStorm.
 * User: zaski
 * Date: 6/11/14
 * Time: 8:57 PM
 */

namespace model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class QuestionModel {

    protected $client;

    public function __construct(Client $client){
        $this->client = $client;
    }

    public function create(array $data = array() ){

        //Construct the query string
        $query =
            "CREATE
                (q:Question {
                    _id: '" . $data['id'] . "',
                    _text: '". $data['text'] . "'}), ";

        $answers =$data['answers'];
        $numAnswers = count($answers);
        $count = 0;
        foreach($answers as $answer){
            $query .=
                "(:Answer {
                    _id: '" . $answer['id'] . "',
                    _text: '" . $answer['text'] . "'
                })-[:IS_ANSWER_OF]->(q)";
            if(++$count === $numAnswers){
                $query .= ";";
            }
            else{
                $query .= ", ";
            }
        }

        //Create the Neo4j query object
        $neoQuery = new  Query (
            $this->client,
            $query
        );

        //Execute query and get new created question
        $result = $neoQuery->getResultSet();

        //TECHNICALDEBT: implement a decent result

        $response = array();
        $response['creation'] = "ok";

        return $response;
    }

} 