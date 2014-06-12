<?php
/**
 * Created by PhpStorm.
 * User: zaski
 * Date: 6/11/14
 * Time: 6:38 PM
 */

namespace Model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class AnswerModel
{

    protected $client;

    public function __construct(Client $client)
    {

        $this->client = $client;

    }

    public function create(array $answer = array())
    {

        $userId = $answer['userId'];

        //Construct the query string
        $queryString =
            "MATCH
                (user:User {qnoow_id: " . $userId . "}),
                (answered:Answer {qnoow_id: '" . $answer['answerId'] . "'}), ";

        $accepted    = $answer['acceptedAnswers'];
        $numAccepted = count($accepted);
        $count       = 0;
        foreach ($accepted as $aa) {
            $queryString .= "(accepted" . $count . ":Answer {qnoow_id: " . $aa['id'] . "}), ";
            ++$count;
        }

        $queryString .=
            "(answered)-[:IS_ANSWER_OF]->(question)
            CREATE
            (user)-[:ANSWERS]->(answered), ";

        for ($count = 0; $count < $numAccepted; ++$count) {
            $queryString .= "(user)-[:ACCEPTS]->(accepted" . $count . "), ";
        }

        $queryString .= "(user)-[:RATES {_rating: " . $answer['rating'] . "}]->(question);";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $queryString
        );

        //Execute query
        $query->getResultSet();
    }

} 