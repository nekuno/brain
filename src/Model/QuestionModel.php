<?php

namespace Model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class QuestionModel
{

    protected $client;

    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    public function create(array $data)
    {
        $params = array(
            'questionId' => $data['id'],
            'questionText' => $data['text'],
        );

        $stringQuery = "CREATE (q:Question {qnoow_id: {questionId}, text: {questionText}})";

        $answers = $data['answers'];
        foreach ($answers as $answer) {
            $answerId =  $answer['id'];
            $params['answerID_'.$answerId] = $answerId;
            $params['answerText_'.$answerId] = $answer['text'];

            $stringQuery .= ", (:Answer {qnoow_id: {answerID_" . $answerId . "}, text: {answerText_" . $answerId . "}})
                    -[:IS_ANSWER_OF]->(q)";
        }

        $stringQuery .= " RETURN q;";

        //Create the Neo4j query object
        $query = new  Query (
            $this->client,
            $stringQuery,
            $params
        );

        $result = array();

        foreach ($query->getResultSet() as $row) {
            $question = array(
                'id'   => $row['q']->getProperty('qnoow_id'),
                'text' => $row['q']->getProperty('text'),
            );

            $result[] = $question;
        }

        return $result;
    }

    public function answer(array $data)
    {

        $params = array(
            'userId' => $data['userId'],
            'answerId' => $data['answerId'],
            'rating' => $data['rating'],
        );
        //Construct the query string
        $queryString =
            "MATCH
                (user:User {qnoow_id: {userId}}),
                (answered:Answer {qnoow_id: {answerId}})";

        $acceptedAnswers = $data['acceptedAnswers'];
        $aliases         = array();
        foreach ($acceptedAnswers as $acceptedAnswer) {
            $acceptedAnswerId = $acceptedAnswer['id'];

            $alias = 'acceptedAnswer' . $acceptedAnswerId;
            $params['acceptedAnswerId_'.$acceptedAnswerId] = $acceptedAnswerId;

            $queryString .= ", (" . $alias . ":Answer {qnoow_id: {acceptedAnswerId_" . $acceptedAnswerId. "}})";
            $aliases[] = $alias;
        }

        $queryString .=
            ", (answered)-[:IS_ANSWER_OF]->(question:Question)
            CREATE
                (user)-[:ANSWERS]->(answered)";

        foreach ($aliases as $alias) {
            $queryString .= ", (user)-[:ACCEPTS]->(" . $alias . ")";
        }

        $queryString .= ", (user)-[:RATES {rating: {rating}}]->(question);";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $queryString,
            $params
        );

        $query->getResultSet();

        return;
    }
} 
