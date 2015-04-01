<?php

namespace Model\User;

use Event\AnswerEvent;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class AnswerModel
 * @package Model\User
 */
class AnswerModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    public function __construct(GraphManager $gm, EventDispatcher $eventDispatcher)
    {

        $this->gm = $gm;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Counts the total results
     * @param array $filters
     * @return int
     */
    public function countTotal(array $filters)
    {

        $count = 0;

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->setParameter('userId', (integer)$filters['id'])
            ->match('(u)-[r:RATES]->(q:Question)')
            ->returns('COUNT(DISTINCT r) AS total');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            $row = $result->current();
            /* @var $row Row */
            $count = $row->offsetGet('total');
        }

        return $count;
    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function create(array $data)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)', '(question:Question)', '(answer:Answer)')
            ->where('user.qnoow_id = { userId } AND id(question) = { questionId } AND id(answer) = { answerId }')
            ->createUnique('(user)-[a:ANSWERS]->(answer)', '(user)-[r:RATES]->(question)')
            ->set('r.rating = { rating }', 'a.private = { isPrivate }', 'a.answeredAt = timestamp()', 'a.explanation = { explanation }')
            ->with('user', 'question', 'answer')
            ->match('(pa:Answer)-[:IS_ANSWER_OF]->(question)')
            ->where('id(pa) IN { acceptedAnswers }')
            ->createUnique('(user)-[:ACCEPTS]->(pa)')
            ->returns('answer')
            ->setParameters($data);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $this->handleAnswerAddedEvent($data);

        return $result;

    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function update(array $data)
    {

        $data['userId'] = intval($data['userId']);
        $data['questionId'] = intval($data['questionId']);
        $data['answerId'] = intval($data['answerId']);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)-[r1:ANSWERS]->(:Answer)-[:IS_ANSWER_OF]->(q:Question)')
            ->where('u.qnoow_id = { userId } AND id(q) = { questionId }')
            ->with('u', 'q', 'r1')
            ->match('(u)-[r2:ACCEPTS]->(:Answer)-[:IS_ANSWER_OF]->(q)')
            ->with('u', 'q', 'r1', 'r2')
            ->match('(u)-[r3:RATES]->(q)')
            ->delete('r1', 'r2', 'r3')
            ->with('u', 'q')
            ->match('(a:Answer)')
            ->where('id(a) = { answerId }')
            ->createUnique('(u)-[r4:ANSWERS]->(a)', '(u)-[r5:RATES]->(q)')
            ->set('r5.rating = { rating }', 'r4.private = { isPrivate }', 'r4.answeredAt = timestamp()', 'r4.explanation = { explanation }')
            ->with('u', 'q', 'a')
            ->optionalMatch('(a1:Answer)-[:IS_ANSWER_OF]->(q)')
            ->where('id(a1) IN { acceptedAnswers }')
            ->createUnique('(u)-[:ACCEPTS]->(a1)')
            ->returns('a AS answer')
            ->setParameters($data);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $this->handleAnswerAddedEvent($data);

        return $result;
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

        $query = $this->gm->createQuery($template, $data);

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

        $query = $this->gm->createQuery($template, $data);

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

        $query = $this->gm->createQuery($template, $data);

        return $query->getResultSet();
    }

    public function getUserAnswer($userId, $questionId, $locale)
    {

        $data['userId'] = (integer)$userId;
        $data['questionId'] = (integer)$questionId;
        $data['locale'] = $locale;

        $template = "MATCH (q:Question), (u:User)"
            . " WHERE u.qnoow_id = {userId} AND id(q) = {questionId} AND HAS(q.text_$locale)"
            . " WITH u, q"
            . " MATCH (u)-[ua:ANSWERS]->(a:Answer)-[:IS_ANSWER_OF]->(q)"
            . " MATCH (u)-[r:RATES]->(q)"
            . " WITH u, a, q, ua, r"
            . " MATCH (a1:Answer)-[:IS_ANSWER_OF]->(q), "
            . " (u)-[:ACCEPTS]->(a2:Answer)-[:IS_ANSWER_OF]->(q)"
            . " WITH u AS user, a AS answer, collect(DISTINCT a2) AS accepts, ua AS userAnswer, r AS rates,"
            . " q AS question, collect(DISTINCT a1) AS answers"
            . " RETURN user, answer, userAnswer, accepts, question, answers, rates"
            . " LIMIT 1;";

        $query = $this->gm->createQuery($template, $data);

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

        $query = $this->gm->createQuery($template, $data);

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

        $query = $this->gm->createQuery($template, $data);

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

        $query = $this->gm->createQuery($template, $data);

        $result = $query->getResultSet();

        foreach ($result as $row) {
            return true;
        }

        return false;
    }

    protected function handleAnswerAddedEvent(array $data)
    {
        $event = new AnswerEvent($data['userId'], $data['questionId']);
        $this->eventDispatcher->dispatch(\AppEvents::ANSWER_ADDED, $event);
    }
}