<?php

namespace Model\Questionnaire;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

/**
 * Class QuestionModel
 * @package Model\Questionnaire
 */
class QuestionModel
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    /**
     * @param int|null $limit
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getAll($limit = 20)
    {

        $data = is_null($limit) ? array() : array('limit' => (integer)$limit);

        $template = "MATCH (q:Question)"
            . " OPTIONAL MATCH (q)<-[:IS_ANSWER_OF]-(a:Answer)"
            . " RETURN q AS question, collect(a) AS answers"
            . " ORDER BY question.ranking DESC";

        if (!is_null($limit)) {
            $template .= " LIMIT {limit}";
        }

        $query = new Query($this->client, $template, $data);

        return $query->getResultSet();
    }

    /**
     * @param $userId
     * @param bool $sortByRanking
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getNextByUser($userId, $sortByRanking = true)
    {

        $data = array(
            'userId' => (integer)$userId
        );

        $template = "MATCH (user:User)"
            . " WHERE user.qnoow_id = {userId}"
            . " OPTIONAL MATCH (user)-[:ANSWERS]->(a:Answer)-[:IS_ANSWER_OF]->(answered:Question)"
            . " OPTIONAL MATCH (user)-[:SKIPS]->(skip:Question)"
            . " OPTIONAL MATCH (:User)-[:REPORTS]->(report:Question)"
            . " WITH user, collect(answered) + collect(skip) + collect(report) AS excluded"
            . " MATCH (q3:Question)<-[:IS_ANSWER_OF]-(a2:Answer)"
            . " WHERE NOT q3 IN excluded"
            . " WITH q3 AS next, collect(DISTINCT a2) AS nextAnswers"
            . " RETURN next, nextAnswers ";

        if ($sortByRanking && $this->sortByRanking()) {
            $template .= " ORDER BY next.ranking DESC";
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
    public function sortByRanking()
    {

        $rand = rand(1, 10);
        if ($rand !== 10) {
            return true;
        }

        return false;
    }

    /**
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getById($questionId)
    {

        $data = array(
            'questionId' => (integer)$questionId,
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
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function create(array $data)
    {

        $data['userId'] = (integer)$data['userId'];

        $data['answers'] = array_values($data['answers']);

        if (!isset($data['text_es'])) {
            $data['text_es'] = null;
        }

        if (!isset($data['text_en'])) {
            $data['text_en'] = null;
        }

        $template = "MATCH (u:User)"
            . " WHERE u.qnoow_id = {userId}"
            . " CREATE (q:Question)-[c:CREATED_BY]->(u)"
            . " SET q.text = {text}, q.text_es = {text_es}, q.text_en = {text_en}, q.timestamp = timestamp(), q.ranking = 0, c.timestamp = timestamp()"
            . " FOREACH (answer in {answers}| CREATE (a:Answer {text: answer.text, text_es: answer.text_es, text_en: answer.text_en})-[:IS_ANSWER_OF]->(q))"
            . " RETURN q;";

        // Create the Neo4j query object
        $query = new  Query(
            $this->client,
            $template,
            $data
        );

        foreach ($query->getResultSet() as $row) {
            return $row;
        }

        return true;
    }

    public function update(array $data)
    {

        $data['id'] = (integer)$data['id'];

        $answers = array();
        if (isset($data['answers'])) {
            $answers = $data['answers'];
            unset($data['answers']);
        }

        $template = "MATCH (q:Question)"
            . " WHERE id(q) = {id}"
            . " SET q.text = {text}";

        if (isset($data['text_es'])) {
            $template .= ", q.text_es = {text_es}";
        }

        if (isset($data['text_en'])) {
            $template .= ", q.text_en = {text_en}";
        }

        $template .= " RETURN q;";

        // Create the Neo4j query object
        $query = new Query(
            $this->client,
            $template,
            $data
        );

        $query->getResultSet();

        foreach ($answers as $answer) {

            $template = "MATCH (a:Answer)"
                . " WHERE id(a) = {id}"
                . " SET a.text = {text}";

            if (isset($answer['text_es'])) {
                $template .= ", a.text_es = {text_es}";
            }

            if (isset($answer['text_en'])) {
                $template .= ", a.text_en = {text_en}";
            }

            $template .= " RETURN a;";

            // Create the Neo4j query object
            $query = new Query(
                $this->client,
                $template,
                $answer
            );

            $query->getResultSet();
        }

        return true;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function skip(array $data)
    {

        $data['questionId'] = (integer)$data['questionId'];
        $data['userId'] = (integer)$data['userId'];

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

        $data['questionId'] = (integer)$data['questionId'];
        $data['userId'] = (integer)$data['userId'];

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

    /**
     * @param $id
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getQuestionStats($id)
    {

        $data['id'] = (integer)$id;

        $template = "MATCH (a:Answer)-[:IS_ANSWER_OF]->(q:Question)"
            . " WHERE id(q) = {id} WITH q, a"
            . " OPTIONAL MATCH ua = (u:User)-[x:ANSWERS]->(a)"
            . " WITH id(a) AS answer, count(x)"
            . " AS nAnswers RETURN answer, nAnswers;";

        $query = new Query($this->client, $template, $data);

        return $query->getResultSet();
    }

    /**
     * @param $questionId
     * @return mixed
     * @throws \Exception
     */
    public function setOrUpdateRankingForQuestion($questionId)
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
            'questionId' => $questionId
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

        foreach ($result as $row) {
            $questionRanking = $row['questionRanking'];
        }

        $response = $questionRanking;

        return $response;

    }

    /**
     * @param $questionId
     * @return mixed
     * @throws \Exception
     */
    public function getRankingForQuestion($questionId)
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
            'questionId' => $questionId
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

        foreach ($result as $row) {
            $questionRanking = $row['questionRanking'];
        }

        $response = $questionRanking;

        return $response;

    }

    /**
     * @param $questionId
     * @return bool
     */
    public function existsQuestion($questionId)
    {

        $data = array(
            'questionId' => (integer)$questionId,
        );

        $template = "MATCH (q:Question) WHERE id(q) = {questionId} RETURN q AS Question";

        $query = new Query($this->client, $template, $data);

        $result = $query->getResultSet();

        foreach ($result as $row) {
            return true;
        }

        return false;
    }
}
