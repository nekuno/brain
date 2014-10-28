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

    public function getAll($limit = 20)
    {

        $data = array('limit' => (integer) $limit);

        $template = "MATCH (q:Question)<-[:IS_ANSWER_OF]-(a:Answer)"
            . " WITH q, a"
            . " RETURN q AS question, collect(a) AS answers"
            . " LIMIT {limit}";

        $query = new Query($this->client, $template, $data);

        return $query->getResultSet();
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
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getById($questionId)
    {

        $data = array(
            'questionId' => (integer) $questionId,
        );

        $template = " MATCH (q:Question)<-[:IS_ANSWER_OF]-(a:Answer)"
            . " WHERE id(q) = {questionId}"
            . " WITH q AS question, collect(a) AS answers"
            . " RETURN question, answers"
            . " LIMIT 1;";

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


    public function setOrUpdateRankingForQuestion ($questionID)
    {

        $queryString = "
        MATCH
            (q:Question)<-[:IS_ANSWER_OF]-(a:Answer)
        WHERE
            id(q) = {questionId}
        OPTIONAL MATCH
            (u:User)-[:ANSWERS]->(a)
        WITH
            q,
            a AS answers,
            count(DISTINCT u) as numOfUsersThatAnswered
        WITH
            q,
            length(collect(answers)) AS numOfAnswers,
            stdev(numOfUsersThatAnswered) AS standardDeviation
        WITH
            q,
            1- (standardDeviation*1.0/numOfAnswers) AS ranking
        OPTIONAL MATCH
            (u:User)-[r:RATES]->(q)
        WITH
            q,
            ranking,
            (1.0/50) * avg(r.rating) AS rating
        WITH
            q,
            0.9 * ranking + 0.1 * rating AS questionRanking
        SET
            q.ranking = questionRanking
        RETURN
            q.ranking AS questionRanking
        ";

        $queryDataArray = array(
            'questionID' => $questionID
        );

        $query = new Query(
            $this->client,
            $queryString,
            $queryDataArray
        );

        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        foreach($result as $row){
            $questionRanking = $row['questionRanking'];
        }

        $response = $questionRanking;

        return $response;

    }

    public function getRankingForQuestion ($questionID)
    {

        $queryString = "
        MATCH
            (q:Question)
        WHERE
            id(q) = {questionId}
        RETURN
            q.ranking AS questionRanking
        ";

        $queryDataArray = array(
            'questionId' => $questionID
        );

        $query = new Query(
            $this->client,
            $queryString,
            $queryDataArray
        );

        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        foreach($result as $row){
            $questionRanking = $row['questionRanking'];
        }

        $response = $questionRanking;

        return $response;

    }
}
