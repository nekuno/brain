<?php

namespace Model\User;

use Event\AnswerEvent;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\Questionnaire\QuestionModel;
use Manager\UserManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnswerModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var QuestionModel
     */
    protected $qm;

    /**
     * @var UserManager
     */
    protected $um;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    public function __construct(GraphManager $gm, QuestionModel $qm, UserManager $um, EventDispatcher $eventDispatcher)
    {

        $this->gm = $gm;
        $this->qm = $qm;
        $this->um = $um;
        $this->eventDispatcher = $eventDispatcher;
    }

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

    public function answer(array $data)
    {
        $this->validate($data);

        if ($this->existsUserAnswer($data['userId'], $data['questionId'])) {
            return $this->update($data);
        } else {
            return $this->create($data);
        }
    }

    public function create(array $data)
    {

        $this->validate($data);

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

        $query->getResultSet();

        $this->handleAnswerAddedEvent($data);

        return $this->getUserAnswer($data['userId'], $data['questionId'], $data['locale']);

    }

    public function update(array $data)
    {

        $this->validate($data);

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
            ->optionalMatch('(answers:Answer)-[:IS_ANSWER_OF]->(q)')
            ->where('id(answers) IN { acceptedAnswers }')
            ->createUnique('(u)-[:ACCEPTS]->(answers)')
            ->returns('a AS answer')
            ->setParameters($data);

        $query = $qb->getQuery();

        $query->getResultSet();

        $this->handleAnswerAddedEvent($data);

        return $this->getUserAnswer($data['userId'], $data['questionId'], $data['locale']);
    }

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

        $query->getResultSet();

        return $this->getUserAnswer($data['userId'], $data['questionId'], $data['locale']);

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

        $user = $this->um->getById($userId);
        $question = $this->qm->getById($questionId, $locale);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)', '(u:User)')
            ->where('u.qnoow_id = { userId }', 'id(q) = { questionId }', "HAS(q.text_$locale)")
            ->setParameter('userId', $user['qnoow_id'])
            ->setParameter('questionId', $question['questionId'])
            ->with('u', 'q')
            ->match('(u)-[ua:ANSWERS]->(a:Answer)-[:IS_ANSWER_OF]->(q)')
            ->match('(u)-[r:RATES]->(q)')
            ->with('u', 'a', 'q', 'ua', 'r')
            ->match('(answers:Answer)-[:IS_ANSWER_OF]->(q)')
            ->match('(u)-[:ACCEPTS]->(acceptedAnswers:Answer)-[:IS_ANSWER_OF]->(q)')
            ->with('u', 'a', 'ua', 'acceptedAnswers', 'q', 'r', 'answers')
            ->orderBy('ID(answers)', 'ID(acceptedAnswers)')
            ->with('u AS user', 'a AS answer', 'ua AS userAnswer', 'COLLECT(DISTINCT acceptedAnswers) AS acceptedAnswers', 'q AS question', 'r AS rates', 'COLLECT(DISTINCT answers) AS answers')
            ->returns('user', 'answer', 'userAnswer', 'acceptedAnswers', 'question', 'rates', 'answers')
            ->limit(1);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException(sprintf('There is not answer for user "%s" to question "%s"', $user['qnoow_id'], $question['questionId']));
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row, $locale);
    }

    /**
     * @param $userId
     * @param array $answer
     * @return int
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function deleteUserAnswer($userId, array $answer)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters(
            array(
                'userId' => $userId,
                'answerId' => $answer['answerId'],
            )
        );

        $qb->match('(a:Answer)', '(u:User)')
            ->where('(id(a) = {answerId})', '(u.qnoow_id = {userId})')
            ->with('a', 'u')
            ->match('(a)-[:IS_ANSWER_OF]->(q:Question)<-[:IS_ANSWER_OF]-(answers:Answer)')
            ->match('(u)-[ua:ANSWERS]->(a)')
            ->optionalMatch('(u)-[rates:RATES]->(q)', '(u)-[accepts:ACCEPTS]->(answers)')
            ->delete('ua', 'rates', 'accepts')
            ->returns('count(ua) as deleted');
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return ($row->offsetGet('deleted'));

    }

    /**
     * @param array $data
     * @param bool $userRequired
     */
    public function validate(array $data, $userRequired = true)
    {

        $errors = array();

        foreach ($this->getFieldsMetadata() as $fieldName => $fieldMetadata) {

            if ($userRequired && $fieldName === 'userId') {
                $fieldMetadata['required'] = true;
            }

            $fieldErrors = array();

            if ($fieldMetadata['required'] === true && !isset($data[$fieldName])) {

                $fieldErrors[] = sprintf('The field "%s" is required', $fieldName);

            } else {

                $fieldValue = isset($data[$fieldName]) ? $data[$fieldName] : null;

                switch ($fieldName) {
                    case 'questionId':
                        if (!is_int($fieldValue)) {
                            $fieldErrors[] = 'questionId must be an integer';
                        } elseif (!$this->existsQuestion($fieldValue)) {
                            $fieldErrors[] = 'Invalid question ID';
                        }
                        break;
                    case 'answerId':
                        if (!is_int($fieldValue)) {
                            $fieldErrors[] = 'answerId must be an integer';
                        } elseif (isset($data['questionId']) && is_int($data['questionId']) && !$this->existsAnswer($data['questionId'], $fieldValue)) {
                            $fieldErrors[] = 'Invalid answer ID';
                        }
                        break;
                    case 'acceptedAnswers':
                        if (!is_array($fieldValue)) {
                            $fieldErrors[] = 'acceptedAnswers must be an array';
                        } else {
                            $acceptedAnswersNum = count($fieldValue);
                            if ($acceptedAnswersNum === 0) {
                                $fieldErrors[] = 'At least one accepted answer needed';
                            } else {
                                foreach ($fieldValue as $acceptedAnswer) {
                                    if (!is_int($acceptedAnswer)) {
                                        $fieldErrors[] = 'acceptedAnswers items must be integers';
                                    } elseif (isset($data['questionId']) && is_int($data['questionId']) && !$this->existsAnswer($data['questionId'], $acceptedAnswer)) {
                                        $fieldErrors[] = 'Invalid accepted answer ID';
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case 'rating':
                        if (!is_int($fieldValue)) {
                            $fieldErrors[] = 'rating must be an integer';
                        } elseif (!in_array($fieldValue, range($fieldMetadata['min'], $fieldMetadata['max']))) {
                            $fieldErrors[] = sprintf('Invalid importance value. Should be between both %d and %d included', $fieldMetadata['min'], $fieldMetadata['max']);
                        }
                        break;
                    case 'isPrivate':
                        if (!is_bool($fieldValue)) {
                            $fieldErrors[] = 'isPrivate must be boolean';
                        }
                        break;
                    case 'explanation':
                        break;
                    case 'userId':
                        if ($fieldValue) {
                            if (!is_int($fieldValue)) {
                                $fieldErrors[] = 'userId must be an integer';
                            } else {
                                try {
                                    $this->um->getById($fieldValue);
                                } catch (NotFoundHttpException $e) {
                                    $fieldErrors[] = $e->getMessage();
                                }
                            }
                        }
                        break;
                    default:
                        break;
                }
            }

            if (count($fieldErrors) > 0) {
                $errors[$fieldName] = $fieldErrors;
            }

        }

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    public function build(Row $row, $locale)
    {

        return array(
            'userAnswer' => $this->buildUserAnswer($row),
            'question' => $this->qm->build($row, $locale),
        );
    }

    protected function buildUserAnswer(Row $row)
    {

        $keys = array('question', 'answer', 'userAnswer', 'rates', 'acceptedAnswers');
        foreach ($keys as $key) {
            if (!$row->offsetExists($key)) {
                throw new \RuntimeException(sprintf('"%s" key needed in row', $key));
            }
        }

        /* @var $question Node */
        $question = $row->offsetGet('question');
        /* @var $answer Node */
        $answer = $row->offsetGet('answer');
        /* @var $userAnswer Node */
        $userAnswer = $row->offsetGet('userAnswer');
        /* @var $rates Relationship */
        $rates = $row->offsetGet('rates');

        $acceptedAnswers = array();
        foreach ($row->offsetGet('acceptedAnswers') as $acceptedAnswer) {
            /* @var $acceptedAnswer Node */
            $acceptedAnswers[] = $acceptedAnswer->getId();
        }

        return array(
            'questionId' => $question->getId(),
            'answerId' => $answer->getId(),
            'acceptedAnswers' => $acceptedAnswers,
            'rating' => $rates->getProperty('rating'),
            'explanation' => $userAnswer->getProperty('explanation'),
            'isPrivate' => $userAnswer->getProperty('private'),
            'answeredAt' => $userAnswer->getProperty('answeredAt'),
        );
    }

    /**
     * @return array
     */
    protected function getFieldsMetadata()
    {

        $metadata = array(
            'questionId' => array(
                'required' => true,
            ),
            'answerId' => array(
                'required' => true,
            ),
            'acceptedAnswers' => array(
                'required' => true,
            ),
            'rating' => array(
                'required' => true,
                'min' => 0,
                'max' => 3,
            ),
            'explanation' => array(
                'required' => true,
            ),
            'isPrivate' => array(
                'required' => true,
            ),
            'userId' => array(
                'required' => false,
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
     * @param $userId
     * @param $questionId
     * @return bool
     * @throws \Exception
     */
    protected function existsUserAnswer($userId, $questionId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)<-[:IS_ANSWER_OF]-(a:Answer)<-[ua:ANSWERS]->(u:User)')
            ->where('id(q) = { questionId }', 'u.qnoow_id = { userId }')
            ->setParameter('questionId', (integer)$questionId)
            ->setParameter('userId', (integer)$userId)
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