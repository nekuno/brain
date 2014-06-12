<?php
/**
 * Created by PhpStorm.
 * User: zaski
 * Date: 6/11/14
 * Time: 8:57 PM
 */

namespace Model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Query\ResultSet;
use Exception\QueryErrorException;

class QuestionModel
{

    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(array $data = array())
    {

        //Construct the query string
        $stringQuery = "CREATE (q:Question {qnoow_id: " . $data['id'] . ", text: '" . $data['text'] . "'}), ";

        $answers    = $data['answers'];
        $numAnswers = count($answers);
        $count      = 0;
        foreach ($answers as $answer) {
            $stringQuery .=
                "(:Answer {qnoow_id: " . $answer['id'] . ", text: '" . $answer['text'] . "'})-[:IS_ANSWER_OF]->(q)";
            if (++$count !== $numAnswers) {
                $stringQuery .= ", ";
            }
        }

        $stringQuery .= " RETURN q";

        //Create the Neo4j query object
        $query = new  Query (
            $this->client,
            $stringQuery
        );

        try{
            $result = $query->getResultSet();
        }catch (\Exception $e){
            throw new QueryErrorException('Error on query');
        }

        return $this->parseResultSet($result);
    }

    private function parseResultSet(ResultSet $resultSet){

        $result = array();

        foreach ($resultSet as $row) {
            $question = array(
                'id' => $row['q']->getProperty('qnoow_id'),
                'text' => $row['q']->getProperty('text'),
            );

            $result[] = $question;
        }

        return $result;
    }

} 