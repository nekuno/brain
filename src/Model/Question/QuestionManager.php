<?php

namespace Model\Question;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Service\Validator\QuestionValidator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuestionManager
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var QuestionValidator
     */
    protected $validator;

    /**
     * @param GraphManager $gm
     * @param QuestionValidator $validator
     */
    public function __construct(GraphManager $gm, QuestionValidator $validator)
    {
        $this->gm = $gm;
        $this->validator = $validator;
    }

    public function userHasCompletedRegisterQuestions($userId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: { userId }})-[:HAS_PROFILE]->(:Profile)<-[:OPTION_OF]-(mode:Mode)')
            ->match('(a:Answer)-[:IS_ANSWER_OF]->(:Question)-[:REGISTERS]-(mode)')
            ->setParameter('userId', (int)$userId)
            ->where('NOT (user)-[:ANSWERS]->(a)')
            ->returns('COUNT(a)');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $thereAreNoRegisterQuestionsLeft = $result->count() == 0;

        return $thereAreNoRegisterQuestionsLeft;
    }

    //TODO: Make answer existence optionalMatch
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
     * @return array
     */
    public function create(array $data)
    {
        $this->validateOnCreate($data);

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
            ->for_each("(answer in {answers}| CREATE (a:Answer {text_$locale: answer})-[:IS_ANSWER_OF]->(q))")
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
        $this->validateOnUpdate($data);

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

    public function delete(array $data)
    {
        $this->validator->validateOnDelete($data);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(question:Question)')
            ->where('id(question) = { questionId }')
            ->setParameter('questionId', (integer)$data['questionId']);

        $qb->optionalMatch('(answer:Answer)-[:IS_ANSWER_OF]->(question)');
        $qb->detachDelete('question', 'answer');

        $result = $qb->getQuery()->getResultSet();

        return $result->count();
    }

    /**
     * @param $id
     * @param $userId
     * @throws \Exception
     */
    public function skip($id, $userId)
    {
        $this->validator->validateUserId($userId);

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(q:Question)', '(u:User)')
            ->where('u.qnoow_id = { userId } AND id(q) = { id }')
            ->setParameter('userId', $userId)
            ->setParameter('id', (integer)$id)
            ->createUnique('(u)-[r:SKIPS]->(q)')
            ->set('r.timestamp = timestamp()')
            ->with('u, q, r')
            ->optionalMatch('(u)-[userAnswer:ANSWERS]->(:Answer)-[:IS_ANSWER_OF]->(q)')
            ->optionalMatch('(u)-[userAccepts:ACCEPTS]->(:Answer)-[:IS_ANSWER_OF]->(q)')
            ->delete('userAnswer, userAccepts')
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
        $this->validator->validateUserId($userId);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(q:Question)', '(u:User)')
            ->where('u.qnoow_id = { userId } AND id(q) = { id }')
            ->setParameter('userId', $userId)
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
     * @return array
     */
    protected function getQuestionStats($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(a:Answer)-[:IS_ANSWER_OF]->(q:Question)')
            ->where('id(q) = { id }')
            ->setParameter('id', (integer)$id)
            ->with('q, a')
            ->optionalMatch('(:Gender {id: "male"})-[:OPTION_OF]->(:Profile)-[:PROFILE_OF]->(:User)-[maleAnswers:ANSWERS]->(a)')
            ->with('q', 'a', 'COUNT(maleAnswers) AS maleAnswersCount')
            ->optionalMatch('(:Gender {id: "female"})-[:OPTION_OF]->(:Profile)-[:PROFILE_OF]->(:User)-[femaleAnswers:ANSWERS]->(a)')
            ->with('a, id(a) AS answer', 'maleAnswersCount', 'COUNT(femaleAnswers) AS femaleAnswersCount')
            ->optionalMatch('(profile:Profile)-[:PROFILE_OF]->(:User)-[:ANSWERS]->(a)')
            ->with('answer', 'maleAnswersCount', 'femaleAnswersCount, profile')
            ->orderBy('answer')
            ->returns('answer', 'maleAnswersCount', 'femaleAnswersCount', 'collect(profile) as profiles');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $stats = array();
        foreach ($result as $row) {
            $profiles = $row['profiles'];
            $oldAnswersCount = 0;
            $youngAnswersCount = 0;
            /** @var Node $profile */
            foreach ($profiles as $profile) {
                $birthday = $profile->getProperty('birthday');
                if ($this->isOlderThanThirty($birthday)) {
                    $oldAnswersCount++;
                } else {
                    $youngAnswersCount++;
                }
            }
            $stats['answers'][] = array(
                'answerId' => $row['answer'],
                'maleAnswersCount' => $row['maleAnswersCount'],
                'femaleAnswersCount' => $row['femaleAnswersCount'],
                'youngAnswersCount' => $youngAnswersCount,
                'oldAnswersCount' => $oldAnswersCount,
            );

            $stats['maleAnswersCount'] = isset($stats['maleAnswersCount']) ? $stats['maleAnswersCount'] + $row['maleAnswersCount'] : $row['maleAnswersCount'];
            $stats['femaleAnswersCount'] = isset($stats['femaleAnswersCount']) ? $stats['femaleAnswersCount'] + $row['femaleAnswersCount'] : $row['femaleAnswersCount'];
            $stats['youngAnswersCount'] = isset($stats['youngAnswersCount']) ? $stats['youngAnswersCount'] + $youngAnswersCount : $youngAnswersCount;
            $stats['oldAnswersCount'] = isset($stats['oldAnswersCount']) ? $stats['oldAnswersCount'] + $oldAnswersCount : $oldAnswersCount;
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

    /**
     * @param array $data
     * @throws ValidationException
     */
    public function validateOnCreate(array $data)
    {
        $this->validator->validateOnCreate($data);
    }

    public function validateOnUpdate(array $data)
    {
        $this->validator->validateOnUpdate($data);
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
        $questionId = $question->getId();

        $registerModes = $this->getRegisterModes($questionId);

        $stats = $this->getQuestionStats($question->getId());
        $maleAnswersStats = array();
        $femaleAnswersStats = array();
        $youngAnswersStats = array();
        $oldAnswersStats = array();
        foreach ($stats['answers'] as $answer) {
            $maleAnswersStats[$answer['answerId']] = $answer['maleAnswersCount'];
            $femaleAnswersStats[$answer['answerId']] = $answer['femaleAnswersCount'];
            $youngAnswersStats[$answer['answerId']] = $answer['youngAnswersCount'];
            $oldAnswersStats[$answer['answerId']] = $answer['oldAnswersCount'];
        }

        $return = array(
            'questionId' => $questionId,
            'maleAnswersCount' => $stats['maleAnswersCount'],
            'femaleAnswersCount' => $stats['femaleAnswersCount'],
            'youngAnswersCount' => $stats['youngAnswersCount'],
            'oldAnswersCount' => $stats['oldAnswersCount'],
            'answers' => array(),
            'registerModes' => $registerModes,
        );

        if (null !== $locale) {
            $return['text'] = $question->getProperty('text_' . $locale);
        } else {
            $return['textEs'] = $question->getProperty('text_es');
            $return['textEn'] = $question->getProperty('text_en');
        }

        foreach ($row->offsetGet('answers') as $answer) {

            /* @var $answer Node */
            $answerArray = array(
                'answerId' => $answer->getId(),
                'maleAnswersCount' => $maleAnswersStats[$answer->getId()],
                'femaleAnswersCount' => $femaleAnswersStats[$answer->getId()],
                'youngAnswersCount' => $youngAnswersStats[$answer->getId()],
                'oldAnswersCount' => $oldAnswersStats[$answer->getId()],
            );

            if (null !== $locale) {
                $answerArray['text'] = $answer->getProperty('text_' . $locale);
            } else {
                $answerArray['textEs'] = $answer->getProperty('text_es');
                $answerArray['textEn'] = $answer->getProperty('text_en');
            }

            $return['answers'][] = $answerArray;
        }

        $return['locale'] = $locale;

        return $return;
    }

    public function getRegisterModes($questionId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(q:Question)')
            ->where('id(q) = {questionId}')
            ->setParameter('questionId', (integer)$questionId);

        $qb->match('(q)-[:REGISTERS]-(m:Mode)')
            ->returns('m.id AS modeId');

        $result = $qb->getQuery()->getResultSet();

        $modes = array();
        foreach ($result as $row)
        {
            $modes[] = $row->offsetGet('modeId');
        }

        return $modes;
    }

    private function isOlderThanThirty($birthday)
    {
        if ($birthday) {
            $birthdayTimeStamp = strtotime($birthday);
            $thirtyYearsAgo = time() - 30 * 365 * 24 * 3600;
            if ($birthdayTimeStamp > $thirtyYearsAgo) {
                return false;
            }

        }

        return true;
    }
}
