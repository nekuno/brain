<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 1/10/14
 * Time: 15:33
 */

namespace Model\Questionnaire;


use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class AnswerModel {

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function create(array $data)
    {

        $template = "MATCH (user:User), (question:Question), (answer:Answer)"
            . " WHERE user.qnoow_id = {userId} AND id(question) = {questionId} AND id(answer) = {answerId}"
            . " CREATE UNIQUE (user)-[:ANSWERS]->(answer)"
            . ", (user)-[r:RATES]->(question)"
            . " SET r.rating = {rating}"
            . " WITH user, question, answer"
            . " OPTIONAL MATCH (pa:Answer)-[:IS_ANSWER_OF]->(question)"
            . " WHERE id(pa) IN {acceptedAnswers}"
            . " CREATE UNIQUE (user)-[:ACCEPTS]->(pa)"
            . " RETURN answer"
        ;

        $template .= ";";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $template,
            $data
        );

        return $query->getResultSet();

    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function update(array $data)
    {

        $template = "MATCH (user:User), (question:Question), (oldAnswer:Answer)"
            . " WHERE user.qnoow_id = {userId} AND id(question) = {questionId} AND id(oldAnswer) = {currentId}"
            . " WITH user, question, oldAnswer"
            . " OPTIONAL MATCH (user)-[r:RATES]->(question)"
            . ", (user)-[r1:ANSWERS]->(oldAnswer)"
            . ", (user)-[r2:ACCEPTS]->(oldAcceptedAnswer:Answer)"
            . " SET r.rating = {rating}"
            . " DELETE r1, r2"
            . " WITH user, question"
            . " OPTIONAL MATCH (answer:Answer)"
            . " WHERE id(answer) = {answerId}"
            . " CREATE UNIQUE (user)-[:ANSWERS]->(answer)"
            . " WITH user, question, answer"
            . " OPTIONAL MATCH (possibleAnswers:Answer)-[:IS_ANSWER_OF]->(question)"
            . " WHERE id(possibleAnswers) IN {acceptedAnswers}"
            . " CREATE UNIQUE (user)-[:ACCEPTS]->(possibleAnswers)"
            . " RETURN answer"
        ;

        $template .= ";";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $template,
            $data
        );

        return $query->getResultSet();
    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function explain(array $data)
    {

        $template = "MATCH"
            . " (user:User)-[r:ANSWERS]->(answer:Answer)"
            . " WHERE user.qnoow_id = {userId} AND id(answer) = {answerId}"
            . " SET r.explanation = {explanation}"
            . " RETURN answer";

        $query = new Query($this->client, $template, $data);

        return $query->getResultSet();

    }
}