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

class QuestionModel
{

    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(array $data)
    {

        //Construct the query string
        $stringQuery = "CREATE (q:Question {qnoow_id: " . $data['id'] . ", text: '" . $data['text'] . "'})";

        $answers = $data['answers'];
        foreach ($answers as $answer) {
            $stringQuery .= ", (:Answer {qnoow_id: " . $answer['id'] . ", text: '" . $answer['text'] . "'})
                    -[:IS_ANSWER_OF]->(q)";
        }

        $stringQuery .= " RETURN q;";

        //Create the Neo4j query object
        $query = new  Query (
            $this->client,
            $stringQuery
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

        //Construct the query string
        $queryString =
            "MATCH
                (user:User {qnoow_id: " . $data['userId'] . "}),
                (answered:Answer {qnoow_id: " . $data['answerId'] . "})";

        $acceptedAnswers = $data['acceptedAnswers'];
        $aliases         = array();
        foreach ($acceptedAnswers as $aa) {
            $alias = 'acceptedAnswer' . $aa['id'];
            $queryString .= ", (" . $alias . ":Answer {qnoow_id: " . $aa['id'] . "})";
            $aliases[] = $alias;
        }

        $queryString .=
            ", (answered)-[:IS_ANSWER_OF]->(question:Question)
            CREATE
                (user)-[:ANSWERS]->(answered)";

        foreach ($aliases as $alias) {
            $queryString .= ", (user)-[:ACCEPTS]->(" . $alias . ")";
        }

        $queryString .= ", (user)-[:RATES {rating: " . $data['rating'] . "}]->(question);";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $queryString
        );

        $query->getResultSet();

        return;

    }

} 