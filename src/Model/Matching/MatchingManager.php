<?php

namespace Model\Matching;

use Event\MatchingEvent;
use Event\MatchingExpiredEvent;
use Everyman\Neo4j\Node;
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

}