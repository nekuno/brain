<?php

namespace Model\Questionnaire;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\UserModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuestionModel
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
     * @param GraphManager $gm
     * @param UserModel $um
     */
    public function __construct(GraphManager $gm, UserModel $um)
    {

        $this->gm = $gm;
        $this->um = $um;
    }

    public function getAll($locale, $skip = null, $limit = null)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)')
            ->where("HAS(q.text_$locale)")
            ->match('(q)<-[:IS_ANSWER_OF]-(a:Answer)')
            ->with('q', 'a')
            ->orderBy('id(a)')
            ->with('q, collect(a) AS answers')
            ->optionalMatch('(q)<-[s:SKIPS]-(u:User)')
            ->with('q', 'answers', 'COUNT(s) as count')
            ->where('count <= 3')
            ->returns('q AS question', 'answers')
            ->orderBy('q.ranking DESC');

        if (!is_null($skip)) {
            $qb->skip($skip);
        }

        if (!is_null($limit)) {
            $qb->limit($limit);
        }

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $return = array();

        foreach ($result as $row) {
            $return[] = $this->build($row, $locale);
        }

        return $return;
    }

    public function getNextByUser($userId, $locale, $sortByRanking = true)
    {

        $user = $this->um->getById($userId);

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(user:User {qnoow_id: { userId }})')
            ->setParameter('userId', $user['qnoow_id'])
            ->optionalMatch('(user)-[:ANSWERS]->(a:Answer)-[:IS_ANSWER_OF]->(answered:Question)')
            ->optionalMatch('(user)-[:SKIPS]->(skip:Question)')
            ->optionalMatch('(:User)-[:REPORTS]->(report:Question)')
            ->with('user', 'collect(answered) + collect(skip) + collect(report) AS excluded')
            ->match('(q3:Question)<-[:IS_ANSWER_OF]-(a2:Answer)')
            ->where('NOT q3 IN excluded', "HAS(q3.text_$locale)")
            ->with('q3 AS question', 'collect(DISTINCT a2) AS answers')
            ->returns('question', 'answers')
            ->orderBy($sortByRanking && $this->sortByRanking() ? 'question.ranking DESC' : 'question.timestamp ASC')
            ->limit(1);

        $query = $qb->getQuery();

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

    public function getById($id, $locale)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)<-[:IS_ANSWER_OF]-(a:Answer)')
            ->where('id(q) = { id }', "HAS(q.text_$locale)")
            ->setParameter('id', (integer)$id)
            ->with('q', 'a')
            ->orderBy('id(a)')
            ->with('q as question, COLLECT(a) AS answers')
            ->returns('question, answers')
            ->limit(1);

        $query = $qb->getQuery();

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
        $data['answers'] = array_map(
            function ($i) {
                return $i['text'];
            },
            $data['answers']
        );

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { userId }})')
            ->create('(q:Question)-[c:CREATED_BY]->(u)')
            ->set("q.text_$locale = { text }", 'q.timestamp = timestamp()', 'q.ranking = 0', 'c.timestamp = timestamp()')
            ->add('FOREACH', "(answer in {answers}| CREATE (a:Answer {text_$locale: answer})-[:IS_ANSWER_OF]->(q))")
            ->returns('q')
            ->setParameters($data);

        $query = $qb->getQuery();

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

        $data['questionId'] = (integer)$data['questionId'];
        $locale = $data['locale'];

        $answers = array();
        if (isset($data['answers'])) {
            $answers = $data['answers'];
            unset($data['answers']);
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)')
            ->where('id(q) = { questionId }')
            ->set("q.text_$locale = { text }")
            ->returns('q')
            ->setParameters($data);

        $query = $qb->getQuery();

        $query->getResultSet();

        foreach ($answers as $answer) {

            $answerData = array(
                'answerId' => (integer)$answer['answerId'],
                'text' => $answer['text'],
            );

            $qb = $this->gm->createQueryBuilder();
            $qb->match('(a:Answer)')
                ->where('id(a) = { answerId }')
                ->set("a.text_$locale = { text }")
                ->returns('a')
                ->setParameters($answerData);

            $query = $qb->getQuery();

            $query->getResultSet();
        }

        return $this->getById($data['questionId'], $locale);
    }

    /**
     * @param $id
     * @param $userId
     * @throws \Exception
     */
    public function skip($id, $userId)
    {

        $user = $this->um->getById($userId);

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(q:Question)', '(u:User)')
            ->where('u.qnoow_id = { userId } AND id(q) = { id }')
            ->setParameter('userId', $user['qnoow_id'])
            ->setParameter('id', (integer)$id)
            ->createUnique('(u)-[r:SKIPS]->(q)')
            ->set('r.timestamp = timestamp()')
            ->returns('r');

        $query = $qb->getQuery();

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

        $user = $this->um->getById($userId);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)', '(u:User)')
            ->where('u.qnoow_id = { userId } AND id(q) = { id }')
            ->setParameter('userId', $user['qnoow_id'])
            ->setParameter('id', (integer)$id)
            ->createUnique('(u)-[r:REPORTS]->(q)')
            ->set('r.reason = { reason }', 'r.timestamp = timestamp()')
            ->setParameter('reason', $reason)
            ->returns('r');

        $query = $qb->getQuery();

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

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(a:Answer)-[:IS_ANSWER_OF]->(q:Question)')
            ->where('id(q) = { id }')
            ->setParameter('id', (integer)$id)
            ->with('q, a')
            ->optionalMatch('ua = (u:User)-[x:ANSWERS]->(a)')
            ->with('id(a) AS answer', 'COUNT(x) AS answersCount')
            ->orderBy('id(a)')
            ->returns('answer, answersCount');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $stats = array();
        foreach ($result as $row) {
            $stats['answers'][] = array(
                'answerId' => $row['answer'],
                'answersCount' => $row['answersCount'],
            );
            if (isset($stats['answersCount'])) {
                $stats['answersCount'] += $row['answersCount'];
            } else {
                $stats['answersCount'] = $row['answersCount'];
            }

        }

        return $stats;
    }

    public function setOrUpdateRankingForQuestion($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)<-[:IS_ANSWER_OF]-(a:Answer)')
            ->where('id(q) = { id }')
            ->setParameter('id', (integer)$id)
            ->optionalMatch('(u:User)-[:ANSWERS]->(a)')
            ->with('q', 'a AS answers', 'COUNT(DISTINCT u) as numOfUsersThatAnswered')
            ->with('q', 'length(collect(answers)) AS numOfAnswers', 'sum(numOfUsersThatAnswered) AS totalAnswers', 'stdevp(numOfUsersThatAnswered) AS standardDeviation')
            ->with('q', '1 - (standardDeviation*1.0/totalAnswers) AS ranking')
            ->optionalMatch('(u:User)-[r:RATES]->(q)')
            ->with('q', 'ranking, (1.0/50) * avg(r.rating) AS rating')
            ->with('q', '0.9 * ranking + 0.1 * rating AS questionRanking')
            ->set('q.ranking = questionRanking')
            ->returns('q.ranking AS questionRanking');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $row = $result->current();

        return $row['questionRanking'];

    }

    public function getRankingForQuestion($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)')
            ->where('id(q) = { id }')
            ->setParameter('id', (integer)$id)
            ->returns('q.ranking AS questionRanking');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $row = $result->current();

        return $row['questionRanking'];

    }

    public function existsQuestion($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)')
            ->where('id(q) = { id }')
            ->setParameter('id', (integer)$id)
            ->returns('q');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return count($result) === 1;
    }

    /**
     * @param array $data
     * @param bool $userRequired
     */
    public function validate(array $data, $userRequired = true)
    {

        $errors = array();

        $locales = array('en', 'es');
        if (!isset($data['locale'])) {
            $errors['locale'] = array('The locale is required');
        } elseif (!in_array($data['locale'], $locales)) {
            $errors['locale'] = array(sprintf('The locale must be one of "%s")', implode('", "', $locales)));
        }

        if (!isset($data['text']) || $data['text'] === '' || !is_string($data['text'])) {
            $errors['text'] = array('The text of the question is required');
        }

        if ($userRequired) {
            if (!isset($data['userId']) || !is_int($data['userId'])) {
                $errors['userId'] = array(sprintf('"userId" is required and must be integer'));
            } else {
                try {
                    $this->um->getById($data['userId']);
                } catch (NotFoundHttpException $e) {
                    $errors['userId'] = array($e->getMessage());
                }
            }
        }

        if (!isset($data['answers']) || !is_array($data['answers']) || count($data['answers']) <= 1) {
            $errors['answers'] = array('At least, two answers are required');
        } elseif (6 < count($data['answers'])) {
            $errors['answers'] = array('Maximum of 6 answers allowed');
        } else {
            foreach ($data['answers'] as $answer) {
                if (!isset($answer['text']) || !is_string($answer['text'])) {
                    $errors['answers'] = array('Each answer must be an array with key "text" string');
                }
            }
        }

        if (count($errors) > 0) {
            $e = new ValidationException('Validation error');
            $e->setErrors($errors);
            throw $e;
        }
    }

    public function build(Row $row, $locale)
    {

        $keys = array('question', 'answers');
        foreach ($keys as $key) {
            if (!$row->offsetExists($key)) {
                throw new \RuntimeException(sprintf('"%s" key needed in row', $key));
            }
        }

        /* @var $question Node */
        $question = $row->offsetGet('question');

        $stats = $this->getQuestionStats($question->getId());
        $answersStats = array();
        foreach ($stats['answers'] as $answer) {
            $answersStats[$answer['answerId']] = $answer['answersCount'];
        }

        $return = array(
            'questionId' => $question->getId(),
            'text' => $question->getProperty('text_' . $locale),
            'answersCount' => $stats['answersCount'],
            'answers' => array(),
        );

        foreach ($row->offsetGet('answers') as $answer) {

            /* @var $answer Node */
            $return['answers'][] = array(
                'answerId' => $answer->getId(),
                'text' => $answer->getProperty('text_' . $locale),
                'answersCount' => $answersStats[$answer->getId()],
            );
        }

        $return['locale'] = $locale;

        return $return;
    }
}
