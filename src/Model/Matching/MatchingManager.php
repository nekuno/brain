<?php

namespace Model\Matching;

use Event\MatchingEvent;
use Event\MatchingExpiredEvent;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Neo4j\GraphManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MatchingManager
{

    const PREFERRED_MATCHING_CONTENT = 'content';
    const PREFERRED_MATCHING_ANSWERS = 'answers';

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param GraphManager $graphManager
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        GraphManager $graphManager
    ) {

        $this->dispatcher = $dispatcher;
        $this->graphManager = $graphManager;
    }

    /**
     * @param $id1
     * @param $id2
     * @return Matching
     * @throws \Exception
     */
    protected function getMatchingBetweenTwoUsers($id1, $id2)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->setParameters(
            array(
                'id1' => (int)$id1,
                'id2' => (int)$id2,
            )
        );

        //Check that both users have at least one url in common
        $qb->match('(u1:User {qnoow_id: {id1}})', '(u2:User {qnoow_id: {id2}})')
            ->optionalMatch('(u1)-[m:MATCHES]-(u2)')
            ->returns('m')
            ->limit('1');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() > 0) {

            $matching = new Matching();

            foreach ($result as $match) {
                /** @var Relationship $matchRelationship */
                if ($matchRelationship = $match['m']) {
                    $matching->setMatching($matchRelationship->getProperty('matching_questions') ?: 0);
                    $matching->setTimestamp($matchRelationship->getProperty('timestamp_questions') ?: 0);
                }
            }

            return $matching;

        } else {
            return null;
        }
    }

    /**
     * @param $rawMatching
     * @return bool
     */
    protected function isNecessaryToRecalculateIt(Matching $rawMatching)
    {
        if (0 == $rawMatching->getMatching()) {
            return false;
        }

        $matchingUpdatedAt = $rawMatching->getTimestamp();

        $currentTimeInMillis = time() * 1000;
        $lastUpdatePlusOneDay = $matchingUpdatedAt + (1000 * 60 * 60 * 24);

        return $lastUpdatePlusOneDay < $currentTimeInMillis;
    }

    /**
     * @param $id1
     * @param $id2
     * @return Matching
     * @throws \Exception
     */
    public function getMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2)
    {
        $matching = $this->getMatchingBetweenTwoUsers($id1, $id2);

        if ($this->isNecessaryToRecalculateIt($matching)) {
            $this->dispatcher->dispatch(\AppEvents::MATCHING_EXPIRED, new MatchingExpiredEvent($id1, $id2, 'answer'));
        }

        return $matching;
    }

    /**
     * @param $id1  int id of the first user
     * @param $id2 int id of the second user
     * @return Matching
     * @throws \Exception
     */
    public function calculateMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->setParameters(
            array(
                'id1' => (integer)$id1,
                'id2' => (integer)$id2
            )
        );

        $qb->match('(u1:User {qnoow_id: { id1 }})', '(u2:User {qnoow_id: { id2 }})')
            ->optionalMatch('(u2)-[:ANSWERS]->(acceptedAnswerU1:Answer)')
            ->where('(u1)-[:ACCEPTS]->(acceptedAnswerU1)')
            ->optionalMatch('(acceptedAnswerU1)-[:IS_ANSWER_OF]->(q:Question)<-[rateAcceptedAnswerU1:RATES]-(u1)')
            ->with(
                'u1',
                'u2',
                $this->weightedRatingSum('rateAcceptedAnswerU1', 'totalRatingAcceptedAnswersU1')
            )
            ->optionalMatch('(u1)-[:ANSWERS]->(acceptedAnswerU2:Answer)')
            ->where('(u2)-[:ACCEPTS]->(acceptedAnswerU2)')
            ->optionalMatch('(acceptedAnswerU2)-[:IS_ANSWER_OF]->(q)<-[rateAcceptedAnswerU2:RATES]-(u2)')
            ->with(
                'u1',
                'u2',
                'totalRatingAcceptedAnswersU1',
                $this->weightedRatingSum('rateAcceptedAnswerU2', 'totalRatingAcceptedAnswersU2')
            )
            ->optionalMatch('(u1)-[rateCommonAnswerU1:RATES]->(commonQuestions:Question)<-[rateCommonAnswerU2:RATES]-(u2)')
            ->with(
                'count(DISTINCT commonQuestions) AS numOfCommonQuestions',
                'totalRatingAcceptedAnswersU1',
                'totalRatingAcceptedAnswersU2',
                $this->weightedRatingSum('rateCommonAnswerU1', 'totalRatingCommonAnswersU1'),
                $this->weightedRatingSum('rateCommonAnswerU2', 'totalRatingCommonAnswersU2')
            )
            ->with(
                'toFloat(numOfCommonQuestions) as numOfCommonQuestions',
                'toFloat(totalRatingAcceptedAnswersU1) AS totalRatingAcceptedAnswersU1',
                'toFloat(totalRatingAcceptedAnswersU2) AS totalRatingAcceptedAnswersU2',
                'toFloat(totalRatingCommonAnswersU1) AS totalRatingCommonAnswersU1',
                'toFloat(totalRatingCommonAnswersU2) AS totalRatingCommonAnswersU2'
            )
            //'error' == max matching depending on common questions
            ->with(
                'numOfCommonQuestions',
                $this->rawMatching(
                    'totalRatingCommonAnswersU1',
                    'totalRatingAcceptedAnswersU1',
                    'rawMatchingU1'
                ),
                $this->rawMatching(
                    'totalRatingCommonAnswersU2',
                    'totalRatingAcceptedAnswersU2',
                    'rawMatchingU2'
                )
            )
            ->with(
                'numOfCommonQuestions',
                'tofloat( sqrt(rawMatchingU1 * rawMatchingU2)) AS rawMatching'
            )
            ->with(
                'CASE
		        WHEN numOfCommonQuestions > 0 THEN rawMatching - (1/numOfCommonQuestions)
		        ELSE rawMatching
                END AS rawMatching'
            )
            ->with(
                'CASE 
                WHEN rawMatching > 0 THEN rawMatching
                ELSE 0
                END AS matching'
            )
            ->returns('matching');

        $result = $qb->getQuery()->getResultSet();

        $matchingValue = 0;
        if ($result->count() == 1) {
            $matchingValue = $result->current()->offsetGet('matching');
        }

        $matchingValue = $matchingValue == null ? 0 : $matchingValue;

        $matching = $this->mergeMatching($id1, $id2, $matchingValue);

        $this->dispatcher->dispatch(\AppEvents::MATCHING_UPDATED, new MatchingEvent($id1, $id2, $matching->getMatching()));

        return $matching;
    }

    protected function mergeMatching($id1, $id2, $matching)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u1:User)', '(u2:User)')
            ->where('u1.qnoow_id = {id1}', 'u2.qnoow_id = {id2}')
            ->merge('(u1)-[m:MATCHES]-(u2)')
            ->set('m.matching_questions = {matching}', 'm.timestamp_questions = timestamp()')
            ->returns('m');

        $qb->setParameters(
            array(
                'id1' => (integer)$id1,
                'id2' => (integer)$id2,
                'matching' => (float)$matching
            )
        );

        $resultSet = $qb->getQuery()->getResultSet();

        if ($resultSet->count() == 0)
        {
            return null;
        }

        /** @var Node $matchingNode */
        $matchingNode = $resultSet->current()->offsetGet('m');

        $matching = new Matching();
        $matching->setMatching($matchingNode->getProperty('matching_questions'));
        $matching->setTimestamp($matchingNode->getProperty('timestamp_questions'));

        return $matching;
    }

    private function weightedRatingSum($variable, $alias)
    {
        return "SUM(CASE toint($variable.rating)
                WHEN 0 THEN 0
                WHEN 1 THEN 1
                WHEN 2 THEN 10
                WHEN 3 THEN 50
                ELSE 0 END) AS $alias";
    }

    private function rawMatching($ratingCommon, $ratingAccepted, $alias)
    {
        return "CASE
                    WHEN $ratingCommon > 0 AND $ratingAccepted > 0
                        THEN tofloat($ratingAccepted/$ratingCommon)
                    ELSE tofloat(0.01)
                END AS $alias";
    }

    public function getDetailedMatching($user1Id, $user2Id)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u1:User{qnoow_id:{user1Id}}), (u2:User{qnoow_id:{user2Id}})')
            ->setParameter('user1Id', (integer)$user1Id)
            ->setParameter('user2Id', (integer)$user2Id)
            ->with('u1', 'u2');

        $qb->match('(u1)-[matches:MATCHES]-(u2)')
            ->with('u1', 'u2', 'matches');

        $qb->optionalMatch('(u1)-[:RATES]->(questionCommon:Question)<-[:RATES]-(u2)')
//            ->with('u1', 'u2', 'matches', '{id: id(questionCommon), text: questionCommon.text_es} AS questionCommon')
            ->with('u1', 'u2', 'matches', 'questionCommon');

        $qb->optionalMatch('(questionCommon)-[:IS_ANSWER_OF]-(answer:Answer)')
            ->with('u1', 'u2', 'matches', 'questionCommon', 'answer');
        
        $qb->match('(u1)-[:ANSWERS]->(u1Answered:Answer)--(questionCommon)')
            ->match('(u2)-[:ANSWERS]->(u2Answered:Answer)--(questionCommon)');

        $qb->with('u1', 'u2', 'matches', 'questionCommon',
            '{id: id(answer), text: answer.text_es} AS answer', 
            '{id: id(u1Answered), text: u1Answered.text_es} AS u1Answered',
            '{id: id(u2Answered), text: u2Answered.text_es} AS u2Answered')
            ->with('u1', 'u2', 'matches', 'questionCommon', 'collect(answer) AS answers', 'collect(u1Answered) AS u1Answered', 'collect(u2Answered) AS u2Answered')
            ->with('u1', 'u2', 'matches', '{id: id(questionCommon), text: questionCommon.text_es} AS questionCommon', 'answers', 'u1Answered', 'u2Answered')
            ->with('u1', 'u2', '{matching: matches.matching_questions, timestamp: matches.timestamp_questions} AS matches', 'questionCommon', 'answers', 'u1Answered', 'u2Answered');

        $qb->returns(
            '{id: id(u1), username: u1.username} AS u1',
            '{id: id(u2), username: u2.username} AS u2',
            'matches', 'questionCommon', 'answers', 'u1Answered', 'u2Answered');

        $result = $qb->getQuery()->getResultSet();
        if ($result->count() === 0 ){
            return array();
        }

        /** @var Row $row */
        $row = $result->current();

        $user1 = $row->offsetGet('u1');
        $user2 = $row->offsetGet('u2');

        /** @var Row $matches */
        $matches = $row->offsetGet('matches');
        $matching = new Matching();
        $matching->setMatching($matches->offsetGet('matching'));
        $matching->setTimestamp($matches->offsetGet('timestamp'));

        $questions = array();
        foreach ($result as $row)
        {
            $questionObject = $row->offsetGet('questionCommon');
            $question = array('id' => $questionObject->offsetGet('id'), 'text' => $questionObject->offsetGet('text'));

            $answersCollect = $row->offsetGet('answers');
            $answers = array();
            /** @var Row $answerObject */
            foreach ($answersCollect as $answerObject)
            {
                $answer = array('id' => $answerObject->offsetGet('id'), 'text' => $answerObject->offsetGet('text'));
                $answers[] = $answer;
            }
            $user1AnsweredCollect = $row->offsetGet('u1Answered');
            $user1Answered = array();
            foreach ($user1AnsweredCollect as $user1AnsweredObject)
            {
                $user1Answered[] = array('id' => $user1AnsweredObject->offsetGet('id'), 'text' => $user1AnsweredObject->offsetGet('text'));
                
            }

            $user2AnsweredCollect = $row->offsetGet('u2Answered');
            $user2Answered = array();
            foreach ($user2AnsweredCollect as $user2AnsweredObject)
            {
                $user2Answered[] = array('id' => $user2AnsweredObject->offsetGet('id'), 'text' => $user2AnsweredObject->offsetGet('text'));

            }

            $question['answers'] = $answers;
            $question['links']['user1Answered'] = $user1Answered;
            $question['links']['user2Answered'] = $user2Answered;

            $questions[] = $question;
        }

        return array(
            'user1' => $user1,
            'user2' => $user2,
            'questions' => $questions,
        );
    }

}