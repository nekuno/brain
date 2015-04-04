<?php

namespace Model\User;

use Event\AnswerEvent;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnswerModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var UserModel
     */
    protected $um;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    public function __construct(GraphManager $gm, UserModel $um, EventDispatcher $eventDispatcher)
    {

        $this->gm = $gm;
        $this->um = $um;
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

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)-[r:ANSWERS]->(answer:Answer)-[:IS_ANSWER_OF]->(question:Question)')
            ->where('user.qnoow_id = { userId } AND id(question) = { questionId }')
            ->set('r.explanation = { explanation }')
            ->returns('answer')
            ->setParameters($data);

        $query = $qb->getQuery();

        return $query->getResultSet();

    }

    /**
     * @param $userId
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getUserAnswers($userId)
    {

        $data['userId'] = (integer)$userId;

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(a:Answer)<-[ua:ANSWERS]-(u:User), (a)-[:IS_ANSWER_OF]-(q:Question)')
            ->with('u', 'a', 'q', 'ua')
            ->where('u.qnoow_id = { userId }')
            ->optionalMatch('(a2:Answer)-[:IS_ANSWER_OF]->(q)')
            ->with('u AS user', 'a AS answer', 'ua.answeredAt AS answeredAt', 'ua.explanation AS explanation', 'q AS question', 'collect(a2) AS answers')
            ->returns('user', 'answer', 'answeredAt', 'explanation', 'question', ' answers')
            ->orderBy('answeredAt DESC')
            ->setParameters($data);

        $query = $qb->getQuery();

        return $query->getResultSet();
    }

    /**
     * @param $userId
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getNumberOfUserAnswers($userId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(a:Answer)<-[ua:ANSWERS]-(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->setParameter('userId', (integer)$userId)
            ->returns('count(ua) AS nOfAnswers');

        $query = $qb->getQuery();

        return $query->getResultSet();
    }

    public function getUserAnswer($userId, $questionId, $locale)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)', '(u:User)')
            ->where('u.qnoow_id = { userId }', 'id(q) = { questionId }', "HAS(q.text_$locale)")
            ->setParameter('userId', (integer)$userId)
            ->setParameter('questionId', (integer)$questionId)
            ->with('u', 'q')
            ->match('(u)-[ua:ANSWERS]->(a:Answer)-[:IS_ANSWER_OF]->(q)')
            ->match('(u)-[r:RATES]->(q)')
            ->with('u', 'a', 'q', 'ua', 'r')
            ->match('(a1:Answer)-[:IS_ANSWER_OF]->(q)', '(u)-[:ACCEPTS]->(a2:Answer)-[:IS_ANSWER_OF]->(q)')
            ->with('u AS user', 'a AS answer', 'collect(DISTINCT a2) AS accepts', 'ua AS userAnswer', 'r AS rates', 'q AS question', 'collect(DISTINCT a1) AS answers')
            ->returns('user', 'answer', 'userAnswer', 'accepts', 'question', 'answers', 'rates')
            ->limit(1);

        $query = $qb->getQuery();

        return $query->getResultSet();
    }

    /**
     * @param array $data
     * @throws ValidationException
     */
    public function validate(array $data)
    {

        $errors = array();

        foreach ($this->getFieldsMetadata() as $fieldName => $fieldMetadata) {
            if ($fieldMetadata['required'] === true && !array_key_exists($fieldName, $data)) {
                $errors[$fieldName] = 'The field ' . $fieldName . ' is required';
            }
        }

        if (count($errors) > 0) {
            $e = new ValidationException('Validation error');
            $e->setErrors($errors);
            throw $e;
        }

        foreach ($this->getFieldsMetadata() as $fieldName => $fieldMetadata) {

            switch ($fieldName) {
                case 'answerId':
                    if (!$this->existsAnswer($data['questionId'], $data['answerId'])) {
                        $errors['answerId'] = 'Invalid answer ID';
                    }
                    break;
                case 'acceptedAnswers':
                    $acceptedAnswersNum = count($data[$fieldName]);
                    if ($acceptedAnswersNum === 0) {
                        $errors['acceptedAnswers'] = 'At least one accepted answer needed';
                    } else {
                        foreach ($data[$fieldName] as $acceptedAnswer) {
                            if (!$this->existsAnswer($data['questionId'], $acceptedAnswer)) {
                                $errors['acceptedAnswers'] = 'Invalid accepted answer ID';
                                break;
                            }
                        }
                    }
                    break;
                case 'rating':
                    if (!in_array($data[$fieldName], range($fieldMetadata['min'], $fieldMetadata['max']))) {
                        $errors['rating'] = sprintf('Invalid importance value. Should be between both %d and %d included', $fieldMetadata['min'], $fieldMetadata['max']);
                    }
                    break;
                case 'isPrivate':
                    break;
                case 'explanation':
                    break;
                case 'userId':
                    try {
                        $this->um->getById($data[$fieldName]);
                    } catch (NotFoundHttpException $e) {
                        $errors['userId'] = $e->getMessage();
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

        if (count($errors) > 0) {
            $e = new ValidationException('Validation error');
            $e->setErrors($errors);
            throw $e;
        }
    }

    /**
     * @return array
     */
    protected function getFieldsMetadata()
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
     * @throws \Exception
     */
    protected function existsAnswer($questionId, $answerId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)<-[:IS_ANSWER_OF]-(a:Answer)')
            ->where('id(q) = { questionId }', 'id(a) = { answerId }')
            ->setParameter('questionId', (integer)$questionId)
            ->setParameter('answerId', (integer)$answerId)
            ->returns('a AS answer');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $result->count() > 0;
    }

    /**
     * @param $questionId
     * @return bool
     * @throws \Exception
     */
    protected function existsQuestion($questionId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)')
            ->where('id(q) = { questionId }')
            ->setParameter('questionId', (integer)$questionId)
            ->returns('q AS question');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $result->count() > 0;
    }

    protected function handleAnswerAddedEvent(array $data)
    {
        $event = new AnswerEvent($data['userId'], $data['questionId']);
        $this->eventDispatcher->dispatch(\AppEvents::ANSWER_ADDED, $event);
    }
}