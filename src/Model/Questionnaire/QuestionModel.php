<?php

namespace Model\Questionnaire;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    public function getAll($locale, $limit = null)
    {

        $data = is_null($limit) ? array() : array('limit' => (integer)$limit);

        $template = "MATCH (q:Question)";
        $template .= " WHERE HAS(q.text_$locale)";
        $template .= " OPTIONAL MATCH (q)<-[:IS_ANSWER_OF]-(a:Answer)"
            . " RETURN q AS question, collect(a) AS answers"
            . " ORDER BY question.ranking DESC";

        if (!is_null($limit)) {
            $template .= " LIMIT {limit}";
        }

        $query = new Query($this->client, $template, $data);

        $result = $query->getResultSet();

        $questions = array();

        foreach ($result as $row) {
            $questions[] = $this->build($row, $locale);
        }

        return $questions;
    }

    /**
     * @param $userId
     * @param $locale
     * @param bool $sortByRanking
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getNextByUser($userId, $locale, $sortByRanking = true)
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
            . " WHERE NOT q3 IN excluded";
        $template .= " AND HAS(q3.text_$locale)";
        $template .= " WITH q3 AS question, collect(DISTINCT a2) AS answers"
            . " RETURN question, answers ";

        if ($sortByRanking && $this->sortByRanking()) {
            $template .= " ORDER BY question.ranking DESC";
        } else {
            $template .= " ORDER BY question.timestamp ASC";
        }

        $template .= " LIMIT 1;";

        $query = new Query(
            $this->client,
            $template,
            $data
        );

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Question not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row, $locale);
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
    public function getById($id, $locale)
    {

        $data = array(
            'id' => (integer)$id,
        );

        $template = "MATCH (q:Question)<-[:IS_ANSWER_OF]-(a:Answer)";
        $template .= " WHERE id(q) = {id}";
        $template .= " AND HAS(q.text_$locale)";
        $template .= " WITH q AS question, collect(a) AS answers"
            . " RETURN question, answers"
            . " LIMIT 1;";

        $query = new Query(
            $this->client,
            $template,
            $data
        );

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Question not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row, $locale);
    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function create(array $data)
    {

        $this->validate($data);

        $locale = $data['locale'];
        $data['userId'] = (integer)$data['userId'];
        $data['answers'] = array_values($data['answers']);

        $template = "MATCH (u:User)"
            . " WHERE u.qnoow_id = {userId}"
            . " CREATE (q:Question)-[c:CREATED_BY]->(u)"
            . " SET q.text_$locale = {text}, q.timestamp = timestamp(), q.ranking = 0, c.timestamp = timestamp()"
            . " FOREACH (answer in {answers}| CREATE (a:Answer {text_$locale: answer})-[:IS_ANSWER_OF]->(q))"
            . " RETURN q;";

        // Create the Neo4j query object
        $query = new  Query(
            $this->client,
            $template,
            $data
        );

        $result = $query->getResultSet();
        /* @var $row Row */
        $row = $result->current();
        /* @var $node Node */
        $node = $row->current();

        return $this->getById($node->getId(), $locale);
    }

    public function update(array $data)
    {

        $this->validate($data, false);

        $data['id'] = (integer)$data['id'];
        $locale = $data['locale'];

        $answers = array();
        if (isset($data['answers'])) {
            $answers = $data['answers'];
            unset($data['answers']);
        }

        $template = "MATCH (q:Question)"
            . " WHERE id(q) = {id}"
            . " SET q.text_$locale = {text}"
            . " RETURN q;";

        // Create the Neo4j query object
        $query = new Query(
            $this->client,
            $template,
            $data
        );

        $query->getResultSet();

        foreach ($answers as $id => $answer) {

            $answerData = array(
                'id' => (integer)$id,
                'text' => $answer,
            );

            $template = "MATCH (a:Answer)"
                . " WHERE id(a) = {id}"
                . " SET a.text_$locale = {text}"
                . " RETURN a;";

            // Create the Neo4j query object
            $query = new Query(
                $this->client,
                $template,
                $answerData
            );

            $query->getResultSet();
        }

        return $this->getById($data['id'], $locale);
    }

    /**
     * @param $id
     * @param $userId
     * @throws \Exception
     */
    public function skip($id, $userId)
    {

        $data = array(
            'id' => (integer)$id,
            'userId' => $userId ? (integer)$userId : $userId,
        );

        $template = "MATCH"
            . " (q:Question)"
            . ", (u:User)"
            . " WHERE u.qnoow_id = {userId} AND id(q) = {id}"
            . " CREATE UNIQUE (u)-[r:SKIPS]->(q)"
            . " SET r.timestamp = timestamp()"
            . " RETURN r;";

        $query = new Query($this->client, $template, $data);

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new \Exception('Can not skip the question');
        }
    }

    /**
     * @param $id
     * @param $userId
     * @param $reason
     * @throws \Exception
     */
    public function report($id, $userId, $reason)
    {

        $data = array(
            'id' => (integer)$id,
            'userId' => $userId ? (integer)$userId : $userId,
            'reason' => $reason,
        );

        $template = "MATCH"
            . " (q:Question)"
            . ", (u:User)"
            . " WHERE u.qnoow_id = {userId} AND id(q) = {id}"
            . " CREATE UNIQUE (u)-[r:REPORTS]->(q)"
            . " SET r.reason = {reason}, r.timestamp = timestamp()"
            . " RETURN r;";

        $query = new Query($this->client, $template, $data);

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new \Exception('Can not report the question');
        }
    }

    /**
     * @param $id
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getQuestionStats($id)
    {

        $data = array(
            'id' => (integer)$id,
        );

        $template = "MATCH (a:Answer)-[:IS_ANSWER_OF]->(q:Question)"
            . " WHERE id(q) = {id} WITH q, a"
            . " OPTIONAL MATCH ua = (u:User)-[x:ANSWERS]->(a)"
            . " WITH id(a) AS answer, count(x)"
            . " AS nAnswers RETURN answer, nAnswers;";

        $query = new Query($this->client, $template, $data);

        $result = $query->getResultSet();

        $stats = array();
        foreach ($result as $row) {
            $stats[$id]['answers'][$row['answer']] = array(
                'id' => $row['answer'],
                'nAnswers' => $row['nAnswers'],
            );
            if (isset($stats[$id]['totalAnswers'])) {
                $stats[$id]['totalAnswers'] += $row['nAnswers'];
            } else {
                $stats[$id]['totalAnswers'] = $row['nAnswers'];
            }

            $stats[$id]['id'] = $id;
        }

        return $stats;
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function setOrUpdateRankingForQuestion($id)
    {

        $data = array(
            'id' => $id
        );

        $template = "
        MATCH
            (q:Question)<-[:IS_ANSWER_OF]-(a:Answer)
        WHERE
            id(q) = {id}
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

        $query = new Query(
            $this->client,
            $template,
            $data
        );

        $result = $query->getResultSet();

        $row = $result->current();

        return $row['questionRanking'];

    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function getRankingForQuestion($id)
    {

        $data = array(
            'id' => $id
        );

        $template = "
        MATCH (q:Question)
        WHERE id(q) = {id}
        RETURN q.ranking AS questionRanking
        ";

        $query = new Query(
            $this->client,
            $template,
            $data
        );

        $result = $query->getResultSet();

        $row = $result->current();

        return $row['questionRanking'];

    }

    /**
     * @param $questionId
     * @return bool
     */
    public function existsQuestion($id)
    {

        $data = array(
            'id' => (integer)$id,
        );

        $template = "MATCH (q:Question) WHERE id(q) = {id} RETURN q AS Question";

        $query = new Query($this->client, $template, $data);

        $result = $query->getResultSet();

        return count($result) === 1;
    }

    /**
     * @param array $data
     * @throws ValidationException
     */
    public function validate(array $data, $includeUser = true)
    {

        $errors = array();

        $locales = array('en', 'es');
        if (!isset($data['locale'])) {
            $errors['locale'] = 'The locale is required';
        } elseif (!in_array($data['locale'], $locales)) {
            $errors['locale'] = 'The locale must be one of "' . implode('", "', $locales) . '"';
        }

        if (!isset($data['text']) || $data['text'] == '') {
            $errors['text'] = 'The text of the question is required';
        }

        if ($includeUser && !isset($data['userId'])) {
            $errors['userId'] = 'The userId is required';
        }

        if (!isset($data['answers']) || !is_array($data['answers']) || count($data['answers']) <= 1) {
            $errors['answers'] = 'At least, two answers are required';
        }

        if (count($errors) > 0) {
            $e = new ValidationException('Validation error');
            $e->setErrors($errors);
            throw $e;
        }
    }

    protected function build(Row $row, $locale)
    {
        /* @var $node Node */
        $node = $row->offsetGet('question');

        $question = array(
            'id' => $node->getId(),
            'text' => $node->getProperty('text_' . $locale),
        );

        foreach ($row->offsetGet('answers') as $answer) {
            /* @var $answer Node */
            $question['answers'][$answer->getId()] = $answer->getProperty('text_' . $locale);
        }

        $question['locale'] = $locale;

        return $question;
    }
}
