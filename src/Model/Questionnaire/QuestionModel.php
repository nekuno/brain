<?php

namespace Model\Questionnaire;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class QuestionModel
{

    protected $client;

    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    /**
     * @param $userId
     * @param bool $sortByRating
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getNextByUser($userId, $sortByRating = true)
    {

        $data = array(
            'userId' => (integer)$userId
        );

        $template = "OPTIONAL MATCH (u:User)-[:ANSWERS]->(a:Answer)-[:IS_ANSWER_OF]->(q:Question)"
            . " WHERE u.qnoow_id = {userId}"
            . " WITH u, q"
            . " OPTIONAL MATCH (u)-[:SKIPS]->(q1:Question), (:User)-[:REPORTS]->(q2:Question)"
            . " WITH u AS user, collect(q) + collect(q1) + collect(q2) AS excluded"
            . " MATCH (q3:Question)<-[:IS_ANSWER_OF]-(a2:Answer)"
            . " WHERE NOT q3 IN excluded"
            . " WITH q3 AS next, collect(DISTINCT a2) AS nextAnswers"
            . " OPTIONAL MATCH (u2:User)-[r:RATES]->(next)"
            . " RETURN next, nextAnswers, sum(r.rating) AS nextRating";



        if ($sortByRating && $this->sortByRating()) {
            $template .= " ORDER BY nextRating DESC";
        } else {
            $template .= " ORDER BY next.timestamp DESC";
        }

        $template .= " LIMIT 1;";

        $query = new Query(
            $this->client,
            $template,
            $data
        );

        return $query->getResultSet();
    }

    /**
     * @return bool
     */
    public function sortByRating()
    {

        $rand = rand(1, 10);
        if ($rand !== 10) {
            return true;
        }

        return false;
    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function create(array $data)
    {

        $data['userId'] = (integer) $data['userId'];

        $data['answers'] = array_values($data['answers']);

        $template = "MATCH (u:User)"
            . " WHERE u.qnoow_id = {userId}"
            . " CREATE (q:Question)<-[c:CREATED_BY]-(u)"
            . " SET q.text = {text}, q.timestamp = timestamp(), c.timestamp = timestamp()"
            . " FOREACH (text in {answers}| CREATE (a:Answer {text: text})-[:IS_ANSWER_OF]->(q))"
            . " RETURN q;";

        //Create the Neo4j query object
        $query = new  Query(
            $this->client,
            $template,
            $data
        );

        foreach ($query->getResultSet() as $row) {
            return $row;
        }
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function skip(array $data)
    {

        $data['questionId'] = (integer) $data['questionId'];
        $data['userId']     = (integer) $data['userId'];

        $template = "MATCH"
            . " (q:Question)"
            . ", (u:User)"
            . " WHERE u.qnoow_id = {userId} AND id(q) = {questionId}"
            . " CREATE UNIQUE (u)-[r:SKIPS]->(q)"
            . " SET r.timestamp = timestamp()"
            . " RETURN r;";

        $query = new Query($this->client, $template, $data);

        foreach ($query->getResultSet() as $row) {
            return $row;
        }
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function report(array $data)
    {

        $data['questionId'] = (integer) $data['questionId'];
        $data['userId'] = (integer) $data['userId'];

        $template = "MATCH"
            . " (q:Question)"
            . ", (u:User)"
            . " WHERE u.qnoow_id = {userId} AND id(q) = {questionId}"
            . " CREATE UNIQUE (u)-[r:REPORTS]->(q)"
            . " SET r.reason = {reason}, r.timestamp = timestamp()"
            . " RETURN r;";

        $query = new Query($this->client, $template, $data);

        foreach ($query->getResultSet() as $row) {
            return $row;
        }
    }

    public function getQuestionStats($id)
    {

            $data['id'] = (integer) $id;

            $template = "MATCH (a:Answer)-[:IS_ANSWER_OF]->(q:Question)"
                . " WHERE id(q) = {id} WITH q, a"
                . " OPTIONAL MATCH ua = (u:User)-[x:ANSWERS]->(a)"
                . " WITH id(a) AS answer, count(x)"
                . " AS nAnswers RETURN answer, nAnswers;"
            ;

            $query = new Query($this->client, $template, $data);

            return $query->getResultSet();
    }
}
