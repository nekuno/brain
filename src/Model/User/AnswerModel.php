<?php

namespace Model\User;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

/**
 * Class AnswerModel
 * @package Model\User
 */
class AnswerModel
{

    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @param \Everyman\Neo4j\Client $client
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    /**
     * Counts the total results from queryset.
     * @param array $filters
     * @throws \Exception
     * @return int
     */
    public function countTotal(array $filters)
    {

        $count = 0;

        $query = "
            MATCH
            (u:User)
            WHERE u.qnoow_id = {UserId}
            MATCH
            (u)-[r:RATES]->(q:Question)
            RETURN
            count(distinct r) as total
            ;
         ";

        //Create the Neo4j query object
        $contentQuery = new Query(
            $this->client,
            $query,
            array(
                'UserId' => (integer)$filters['id'],
            )
        );

        //Execute query
        try {
            $result = $contentQuery->getResultSet();

            foreach ($result as $row) {
                $count = $row['total'];
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $count;
    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function create(array $data)
    {

        $template = "MATCH (user:User), (question:Question), (answer:Answer)"
            . " WHERE user.qnoow_id = {userId} AND id(question) = {questionId} AND id(answer) = {answerId}"
            . " CREATE UNIQUE (user)-[a:ANSWERS]->(answer)"
            . ", (user)-[r:RATES]->(question)"
            . " SET r.rating = {rating}, a.private = {isPrivate}"
            . ", a.answeredAt = timestamp(), a.explanation = {explanation}"
            . " WITH user, question, answer"
            . " OPTIONAL MATCH (pa:Answer)-[:IS_ANSWER_OF]->(question)"
            . " WHERE id(pa) IN {acceptedAnswers}"
            . " CREATE UNIQUE (user)-[:ACCEPTS]->(pa)"
            . " RETURN answer";

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

        $data['userId'] = (integer)$data['userId'];
        $data['questionId'] = (integer)$data['questionId'];

        $template = "MATCH (u:User)-[r1:ANSWERS]->(a1:Answer)-[:IS_ANSWER_OF]->(q:Question)"
            . ", (u)-[r2:ACCEPTS]->(a2:Answer)-[:IS_ANSWER_OF]->(q)"
            . ", (u)-[r3:RATES]->(q)"
            . " WHERE u.qnoow_id = {userId} AND id(q) = {questionId}"
            . " DELETE r1, r2, r3"
            . " WITH u AS user, q AS question"
            . " MATCH (a2:Answer)"
            . " WHERE id(a2) = {answerId}"
            . " CREATE UNIQUE (user)-[r4:ANSWERS]->(a2),  (user)-[r5:RATES]->(question)"
            . " SET r5.rating = {rating}, r4.private = {isPrivate}"
            . ", r4.answeredAt = timestamp(), r4.explanation = {explanation}"
            . " WITH user, question, a2 as answer"
            . " OPTIONAL MATCH (a3:Answer)-[:IS_ANSWER_OF]->(question)"
            . " WHERE id(a3) IN {acceptedAnswers}"
            . " CREATE UNIQUE (user)-[:ACCEPTS]->(a3)"
            . " RETURN answer";

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

        $data['userId'] = (integer)$data['userId'];
        $data['questionId'] = (integer)$data['questionId'];

        $template = "MATCH"
            . " (user:User)-[r:ANSWERS]->(answer:Answer)-[:IS_ANSWER_OF]->(question:Question)"
            . " WHERE user.qnoow_id = {userId} AND id(question) = {questionId}"
            . " SET r.explanation = {explanation}"
            . " RETURN answer";

        $query = new Query($this->client, $template, $data);

        return $query->getResultSet();

    }

    /**
     * @param $userId
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getUserAnswers($userId)
    {

        $data['userId'] = (integer)$userId;

        $template = "MATCH (a:Answer)<-[ua:ANSWERS]-(u:User), (a)-[:IS_ANSWER_OF]-(q:Question)"
            . " WITH u, a, q, ua"
            . " WHERE u.qnoow_id = {userId}"
            . " OPTIONAL MATCH (a2:Answer)-[:IS_ANSWER_OF]->(q)"
            . " WITH u AS user, a AS answer, ua.answeredAt AS answeredAt, ua.explanation AS explanation, q AS question, collect(a2) AS answers"
            . " RETURN user, answer, answeredAt, explanation, question, answers"
            . " ORDER BY answeredAt DESC;";

        $query = new Query($this->client, $template, $data);

        return $query->getResultSet();
    }

    /**
     * @param $userId
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getNumberOfUserAnswers($userId)
    {

        $data['userId'] = (integer)$userId;

        $template = "MATCH (a:Answer)<-[ua:ANSWERS]-(u:User)"
            . " WHERE u.qnoow_id = {userId}"
            . " RETURN count(ua) AS nOfAnswers;";

        $query = new Query($this->client, $template, $data);

        return $query->getResultSet();
    }

    /**
     * @param $data
     * @return array
     */
    public function validate($data)
    {

        $errors = array();

        foreach ($this->getFieldsMetadata() as $fieldName => $fieldMetadata) {
            if ($fieldMetadata['required'] === true && !array_key_exists($fieldName, $data)) {
                $errors[] = 'The field ' . $fieldName . ' is required';
            }
        }

        if (count($errors)) {
            return $errors;
        }

        foreach ($this->getFieldsMetadata() as $fieldName => $fieldMetadata) {

            switch ($fieldName) {
                case 'answerId':
                    if (!$this->existsAnswer($data['questionId'], $data['answerId'])) {
                        $errors[] = 'Invalid answer ID';
                    }
                    break;
                case 'acceptedAnswers':
                    $acceptedAnswersNum = count($data[$fieldName]);
                    if ($acceptedAnswersNum === 0) {
                        $errors[] = '1 accepted answers is needed at least';
                    } else {
                        foreach ($data[$fieldName] as $acceptedAnswer) {
                            if (!$this->existsAnswer($data['questionId'], $acceptedAnswer)) {
                                $errors[] = 'Invalid accepted answer ID';
                            }
                        }
                    }

                    break;
                case 'rating':
                    if (!in_array($data[$fieldName], range($fieldMetadata['min'], $fieldMetadata['max']))) {
                        $errors[] = 'Invalid importance value. Should be between both 0 and 3 included';
                    }
                    break;
                case 'isPrivate':
                    break;
                case 'explanation':
                    break;
                case 'userId':
                    if (!$this->existsUser($data[$fieldName])) {
                        $errors[] = 'Invalid user ID';
                    }
                    break;
                case 'questionId':
                    if (!$this->existsQuestion($data[$fieldName])) {
                        $errors[] = 'Invalid question ID';
                    }
                    break;
                default:
                    break;
            }
        }

        // has one answer at least
        // has one accepted answer at least
        // has importance set

        return $errors;
    }

    /**
     * @return array
     */
    public function getFieldsMetadata()
    {

        $metadata = array(
            'answerId' => array(
                'type' => 'id',
                'required' => true,
            ),
            'acceptedAnswers' => array(
                'type' => 'checkbox',
                'required' => true,
                'multiple' => true
            ),
            'isPrivate' => array(
                'type' => 'checkbox',
                'required' => false,
                'multiple' => false
            ),
            'rating' => array(
                'type' => 'range',
                'step' => 1,
                'required' => true,
                'min' => 0,
                'max' => 3,
            ),
            'explanation' => array(
                'type' => 'text',
                'required' => false
            ),
            'userId' => array(
                'type' => 'id',
                'required' => true
            ),
            'questionId' => array(
                'type' => 'id',
                'required' => true
            ),
        );

        return $metadata;
    }

    /**
     * @param $questionId
     * @param $answerId
     * @return bool
     */
    public function existsAnswer($questionId, $answerId)
    {

        $data = array(
            'questionId' => (integer)$questionId,
            'answerId' => (integer)$answerId,
        );

        $template = "MATCH (q:Question)<-[:IS_ANSWER_OF]-(a:Answer)"
            . " WHERE id(q) = {questionId} AND id(a) = {answerId}"
            . " RETURN a AS answer";

        $query = new Query($this->client, $template, $data);

        $result = $query->getResultSet();

        foreach ($result as $row) {
            return true;
        }

        return false;
    }

    /**
     * @param $userId
     * @return bool
     */
    public function existsUser($userId)
    {

        $data = array(
            'userId' => (integer)$userId,
        );

        $template = "MATCH (u:User)"
            . " WHERE u.qnoow_id = {userId}"
            . " RETURN u AS user";

        $query = new Query($this->client, $template, $data);

        $result = $query->getResultSet();

        foreach ($result as $row) {
            return true;
        }

        return false;
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