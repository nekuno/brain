<?php

namespace Model\Questionnaire;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\UserModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class QuestionModel
 * @package Model\Questionnaire
 */
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
            ->optionalMatch('(q)<-[:IS_ANSWER_OF]-(a:Answer)')
            ->returns('q, collect(a) AS answers')
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
            ->with('q, COLLECT(a) AS answers')
            ->returns('q, answers')
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
        $data['answers'] = array_values($data['answers']);

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

        $query = $this->gm->createQuery($template, $data);

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

            $query = $this->gm->createQuery($template, $answerData);

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
            ->with('id(a) AS answer', 'COUNT(x) AS nAnswers')
            ->returns('answer, nAnswers');

        $query = $qb->getQuery();

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
        MATCH (q:Question)<-[:IS_ANSWER_OF]-(a:Answer)
        WHERE id(q) = {id}
        OPTIONAL MATCH (u:User)-[:ANSWERS]->(a)
        WITH q, a AS answers, COUNT(DISTINCT u) as numOfUsersThatAnswered
        WITH
            q,
            length(collect(answers)) AS numOfAnswers,
            sum(numOfUsersThatAnswered) AS totalAnswers,
            stdevp(numOfUsersThatAnswered) AS standardDeviation
        WITH
            q,
            1- (standardDeviation*1.0/totalAnswers) AS ranking
        OPTIONAL MATCH (u:User)-[r:RATES]->(q)
        WITH q, ranking, (1.0/50) * avg(r.rating) AS rating
        WITH q, 0.9 * ranking + 0.1 * rating AS questionRanking
        SET q.ranking = questionRanking
        RETURN q.ranking AS questionRanking
        ";

        $query = $this->gm->createQuery($template, $data);

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

        $query = $this->gm->createQuery($template, $data);

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

        $query = $this->gm->createQuery($template, $data);

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
        } elseif (6 < count($data['answers'])) {
            $errors['answers'] = 'Maximum of 6 answers allowed';
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

        $stats = $this->getQuestionStats($node->getId());

        $question = array(
            'id' => $node->getId(),
            'text' => $node->getProperty('text_' . $locale),
            'totalAnswers' => $stats[$node->getId()]['totalAnswers'],
        );

        foreach ($row->offsetGet('answers') as $answer) {

            /* @var $answer Node */
            $question['answers'][$answer->getId()] = array(
                'id' => $answer->getId(),
                'text' => $answer->getProperty('text_' . $locale),
                'nAnswers' => $stats[$node->getId()]['answers'][$answer->getId()]['nAnswers'],
            );
        }

        $question['locale'] = $locale;

        return $question;
    }
}
