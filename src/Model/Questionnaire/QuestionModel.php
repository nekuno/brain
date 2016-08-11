<?php

namespace Model\Questionnaire;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Manager\UserManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuestionModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var UserManager
     */
    protected $um;

    /**
     * @param GraphManager $gm
     * @param UserManager $um
     */
    public function __construct(GraphManager $gm, UserManager $um)
    {

        $this->gm = $gm;
        $this->um = $um;
    }

    public function getAll($locale, $skip = null, $limit = null)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)')
            ->where("EXISTS(q.text_$locale)")
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
        $divisiveQuestion = $this->getNextDivisiveQuestionByUserId($userId, $locale);

        if ($divisiveQuestion) {
            return $divisiveQuestion;
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: { userId }})')
            ->setParameter('userId', (int)$userId)
            ->optionalMatch('(user)-[:ANSWERS]->(a:Answer)-[:IS_ANSWER_OF]->(answered:Question)')
            ->optionalMatch('(user)-[:SKIPS]->(skip:Question)')
            ->optionalMatch('(:User)-[:REPORTS]->(report:Question)')
            ->with('user', 'collect(answered) + collect(skip) + collect(report) AS excluded')
            ->match('(q3:Question)<-[:IS_ANSWER_OF]-(a2:Answer)')
            ->where('NOT q3 IN excluded', "EXISTS(q3.text_$locale)")
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

	public function userHasCompletedRegisterQuestions($userId)
	{
		$qb = $this->gm->createQueryBuilder();

		$qb->match('(user:User {qnoow_id: { userId }})', '(a:Answer)-[:IS_ANSWER_OF]->(:RegisterQuestion)')
		   ->setParameter('userId', (int)$userId)
		   ->where('NOT (user)-[:ANSWERS]->(a)')
		   ->returns('COUNT(a)');

		$query = $qb->getQuery();
		$result = $query->getResultSet();

		if ($result > 0) {
			return true;
		}

		return false;
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
            ->where('id(q) = { id }', "EXISTS(q.text_$locale)")
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
            ->where('NOT q:RegisterQuestion', 'u.qnoow_id = { userId } AND id(q) = { id }')
            ->setParameter('userId', $user->getId())
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
            ->setParameter('userId', $user->getId())
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
            ->optionalMatch('(:Gender {id: "male"})-[:OPTION_OF]->(:Profile)-[:PROFILE_OF]->(:User)-[maleAnswers:ANSWERS]->(a)')
            ->with('q', 'a', 'COUNT(maleAnswers) AS maleAnswersCount')
            ->optionalMatch('(:Gender {id: "female"})-[:OPTION_OF]->(:Profile)-[:PROFILE_OF]->(:User)-[femaleAnswers:ANSWERS]->(a)')
            ->with('id(a) AS answer', 'maleAnswersCount', 'COUNT(femaleAnswers) AS femaleAnswersCount')
            ->orderBy('id(a)')
            ->returns('answer', 'maleAnswersCount', 'femaleAnswersCount');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $stats = array();
        foreach ($result as $row) {
            $stats['answers'][] = array(
                'answerId' => $row['answer'],
                'maleAnswersCount' => $row['maleAnswersCount'],
                'femaleAnswersCount' => $row['femaleAnswersCount'],
            );

            $stats['maleAnswersCount'] = isset($stats['maleAnswersCount']) ? $stats['maleAnswersCount'] + $row['maleAnswersCount'] : $row['maleAnswersCount'];
            $stats['femaleAnswersCount'] = isset($stats['femaleAnswersCount']) ? $stats['femaleAnswersCount'] + $row['femaleAnswersCount'] : $row['femaleAnswersCount'];
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
     * @throws ValidationException
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
            throw new ValidationException($errors);
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

        $isRegisterQuestion = false;
        /** @var Label $label */
        foreach ($question->getLabels() as $label) {
            if ($label->getName() == 'RegisterQuestion') {
                $isRegisterQuestion = true;
            }
        }

        $stats = $this->getQuestionStats($question->getId());
        $maleAnswersStats = array();
        $femaleAnswersStats = array();
        foreach ($stats['answers'] as $answer) {
            $maleAnswersStats[$answer['answerId']] = $answer['maleAnswersCount'];
            $femaleAnswersStats[$answer['answerId']] = $answer['femaleAnswersCount'];
        }

        $return = array(
            'questionId' => $question->getId(),
            'text' => $question->getProperty('text_' . $locale),
            'maleAnswersCount' => $stats['maleAnswersCount'],
            'femaleAnswersCount' => $stats['femaleAnswersCount'],
            'answers' => array(),
            'isRegisterQuestion' => $isRegisterQuestion,
        );

        foreach ($row->offsetGet('answers') as $answer) {

            /* @var $answer Node */
            $return['answers'][] = array(
                'answerId' => $answer->getId(),
                'text' => $answer->getProperty('text_' . $locale),
                'maleAnswersCount' => $maleAnswersStats[$answer->getId()],
                'femaleAnswersCount' => $femaleAnswersStats[$answer->getId()],
            );
        }

        $return['locale'] = $locale;

        return $return;
    }

    /**
     * @param $preselected integer how many questions are preselected by rating to be analyzed
     * @return array
     */
    public function getUncorrelatedQuestions($preselected = 50)
    {
        $qb = $this->gm->createQueryBuilder();

        $n = (integer)$preselected;
        $parameters = array('preselected' => $n);
        $qb->setParameters($parameters);

        $qb->match('(q:Question)')
            ->with('q')
            ->orderBy('q.ranking DESC')
            ->limit('{preselected}')
            ->with('collect(q) AS questions')
            ->match('(q1:Question),(q2:Question)')
            ->where(
                '(q1 in questions) AND (q2 in questions)',
                'id(q1)<id(q2)'
            )
            ->with('q1,q2');

        $qb->match('(q1)<-[:IS_ANSWER_OF]-(a1:Answer)')
            ->match('(q2)<-[:IS_ANSWER_OF]-(a2:Answer)')
            ->optionalMatch('(a1)<-[:ANSWERS]-(u:User)-[:ANSWERS]->(a2)')
            ->with('id(q1) AS q1,id(q2) AS q2,id(a1) AS a1,id(a2) AS a2,count(distinct(u)) AS users')
            ->with('q1, q2, sum(users) as totalUsers,  stdevp(users) AS std, (count(distinct(a1))+count(distinct(a2))) AS answers')
            ->where('totalUsers>0')
            ->with('q1, q2, std/totalUsers as normstd, answers')
            ->with('q1,q2,normstd*sqrt(answers) as finalstd')
            ->returns('q1,q2,finalstd');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $correlations = array();
        /* @var $row Row */
        foreach ($result as $row) {
            $correlations[$row->offsetGet('q1')][$row->offsetGet('q2')] = $row->offsetGet('finalstd');
        }

        $correctCorrelations = array();
        foreach ($correlations as $q1 => $array) {
            foreach ($correlations as $q2 => $array2) {
                if (!($q1 < $q2)) {
                    continue;
                }
                $correctCorrelations[$q1][$q2] = isset($correlations[$q1][$q2]) ? $correlations[$q1][$q2] : 1;
            }
        }

//        return $correlations;

        //Size fixed at 4 questions / set
        $minimum = 600;
        $questions = array();
        foreach ($correctCorrelations as $q1 => $c1) {
            foreach ($correctCorrelations as $q2 => $c2) {
                foreach ($correctCorrelations as $q3 => $c3) {
                    foreach ($correctCorrelations as $q4 => $c4) {
                        if (!($q1 < $q2 && $q2 < $q3 && $q3 < $q4)) {
                            continue;
                        }
                        $foursome = $correctCorrelations[$q1][$q2] +
                            $correctCorrelations[$q2][$q3] +
                            $correctCorrelations[$q1][$q3] +
                            $correctCorrelations[$q1][$q4] +
                            $correctCorrelations[$q2][$q4] +
                            $correctCorrelations[$q3][$q4];
                        if ($foursome < $minimum) {
                            $minimum = $foursome;
                            $questions = array(
                                'q1' => $q1,
                                'q2' => $q2,
                                'q3' => $q3,
                                'q4' => $q4
                            );
                        }
                    }
                }
            }
        }

        return array(
            'totalCorrelation' => $minimum,
            'questions' => $questions
        );

    }

    public function setDivisiveQuestions(array $ids)
    {
        $questions = array();
        foreach ($ids as $id) {
            $questions[] = $this->setDivisiveQuestion($id);
        }

        return $questions;
    }

    public function setDivisiveQuestion($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $parameters = array('questionId' => (integer)$id);
        $qb->setParameters($parameters);

        $qb->match('(q:Question)')
            ->where('id(q)={questionId}')
            ->set('q :RegisterQuestion')
            ->returns('q');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $row->offsetGet('q');
    }

    /**
     * @return integer
     * @throws \Exception
     */
    public function unsetDivisiveQuestions()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(q:RegisterQuestion)')
            ->remove('q :RegisterQuestion')
            ->returns('count(q) AS c');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $row->offsetGet('c');
    }

    public function getDivisiveQuestions($locale)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:RegisterQuestion)')
            ->where("EXISTS(q.text_$locale)")
            ->match('(q)<-[:IS_ANSWER_OF]-(a:Answer)')
            ->with('q', 'a')
            ->orderBy('id(a)')
            ->with('q, collect(a) AS answers')
            ->returns('q AS question', 'answers')
            ->orderBy('q.ranking DESC');

        $query = $qb->getQuery();
        $result = $query->getResultSet();
        $return = array();

        foreach ($result as $row) {
            $return[] = $this->build($row, $locale);
        }

        return $return;
    }

    protected function getNextDivisiveQuestionByUserId($id, $locale)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: { userId }})')
            ->setParameter('userId', (int)$id)
            ->optionalMatch('(user)-[:ANSWERS]->(a:Answer)-[:IS_ANSWER_OF]->(answered:RegisterQuestion)')
            ->with('user', 'collect(answered) AS excluded')
            ->match('(q3:RegisterQuestion)<-[:IS_ANSWER_OF]-(a2:Answer)')
            ->where('NOT q3 IN excluded', "EXISTS(q3.text_$locale)")
            ->with('q3 AS question', 'collect(DISTINCT a2) AS answers')
            ->returns('question', 'answers')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if (count($result) === 1) {
            /* @var $divisiveQuestions Row */
            $row = $result->current();

            return $this->build($row, $locale);
        }

        return false;
    }
}
