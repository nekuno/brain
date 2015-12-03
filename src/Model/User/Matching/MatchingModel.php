<?php

namespace Model\User\Matching;

use Event\MatchingExpiredEvent;
use Model\Neo4j\GraphManager;
use Model\User\AnswerModel;
use Model\User\ContentPaginatedModel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MatchingModel
{

    const PREFERRED_MATCHING_CONTENT = 'content';
    const PREFERRED_MATCHING_ANSWERS = 'answers';

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * @var \Model\User\ContentPaginatedModel
     */
    protected $contentPaginatedModel;

    /**
     * @var \Model\User\AnswerModel
     */
    protected $answerModel;

    /**
     * @param EventDispatcher $dispatcher
     * @param GraphManager $graphManager
     * @param \Model\User\ContentPaginatedModel $contentPaginatedModel
     * @param \Model\User\AnswerModel $answerModel
     */
    public function __construct(
        EventDispatcher $dispatcher,
        GraphManager $graphManager,
        ContentPaginatedModel $contentPaginatedModel,
        AnswerModel $answerModel
    )
    {

        $this->dispatcher = $dispatcher;
        $this->graphManager = $graphManager;
        $this->contentPaginatedModel = $contentPaginatedModel;
        $this->answerModel = $answerModel;
    }

    /**
     * @param $id1
     * @param $id2
     * @return array
     * @throws \Exception
     */
    public function getMatchingBetweenTwoUsers($id1, $id2)
    {

        $qb = $this->graphManager->createQueryBuilder();

        $qb->setParameters(array(
            'id1' => (int)$id1,
            'id2' => (int)$id2,
        ));

        //Check that both users have at least one url in common
        $qb->match('(u1:User {qnoow_id: {id1}})', '(u2:User {qnoow_id: {id2}})')
            ->optionalMatch('(u1)-[m:MATCHES]-(u2)')
            ->returns('m')
            ->limit('1');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() > 0) {

            $matching = null;

            foreach ($result as $match) {
                if ($match['m']) {
                    $matching['matching_questions'] = $match['m']->getProperty('matching_questions') ?: 0;
                    $matching['timestamp_questions'] = $match['m']->getProperty('timestamp_questions') ?: 0;
                }
            }

            return $matching;

        } else {
            return null;
        }
    }

    /**
     * @param $rawMatching
     * @param $tsIndex
     * @param $matchingIndex
     * @return int
     */
    public function isNecessaryToRecalculateIt($rawMatching, $tsIndex, $matchingIndex)
    {

        if (isset($rawMatching[$matchingIndex]) && $rawMatching[$matchingIndex] !== null) {
            $matchingUpdatedAt = $rawMatching[$tsIndex] ? $rawMatching[$tsIndex] : 0;
            $currentTimeInMillis = time() * 1000;
            $lastUpdatePlusOneDay = $matchingUpdatedAt + (1000 * 60 * 60 * 24);
            if ($lastUpdatePlusOneDay < $currentTimeInMillis) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $id1
     * @param $id2
     * @return int
     * @throws \Exception
     */
    public function getMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2)
    {

        $rawMatching = $this->getMatchingBetweenTwoUsers($id1, $id2);

        if ($this->isNecessaryToRecalculateIt($rawMatching, 'timestamp_questions', 'matching_questions')) {
            $this->dispatcher->dispatch(\AppEvents::MATCHING_EXPIRED, new MatchingExpiredEvent($id1, $id2, 'answer'));
        }

        $response['matching'] = $rawMatching['matching_questions'] ? $rawMatching['matching_questions'] : 0;

        return $response;
    }

    /**
     * @param $id1  int id of the first user
     * @param $id2 int id of the second user
     * @return float matching by questions between both users
     * @throws \Exception
     */
    public function calculateMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2)
    {

        //Calculate matching

        $qb = $this->graphManager->createQueryBuilder();

        $qb->setParameters(array(
            'id1' => (integer)$id1,
            'id2' => (integer)$id2
        ));

        $qb->match('(u1:User {qnoow_id: { id1 }})', '(u2:User {qnoow_id: { id2 }})')
            ->optionalMatch('(u1)-[:ACCEPTS]->(acceptedAnswerU1:Answer)<-[:ANSWERS]-(u2)',
                '(acceptedAnswerU1)-[:IS_ANSWER_OF]->(:Question)<-[rateAcceptedAnswerU1:RATES]-(u1)')
            ->with('u1', 'u2',
                $this->weightedRatingSum('rateAcceptedAnswerU1', 'totalRatingAcceptedAnswersU1'))
            ->optionalMatch('(u2)-[:ACCEPTS]->(acceptedAnswerU2:Answer)<-[:ANSWERS]-(u1)',
                '(acceptedAnswerU2)-[:IS_ANSWER_OF]->(:Question)<-[rateAcceptedAnswerU2:RATES]-(u2)')
            ->with('u1', 'u2', 'totalRatingAcceptedAnswersU1',
                $this->weightedRatingSum('rateAcceptedAnswerU2', 'totalRatingAcceptedAnswersU2'))
            ->optionalMatch('(u1)-[rateCommonAnswerU1:RATES]->(commonQuestions:Question)<-[rateCommonAnswerU2:RATES]-(u2)')
            ->with('count(DISTINCT commonQuestions) AS numOfCommonQuestions',
                'totalRatingAcceptedAnswersU1', 'totalRatingAcceptedAnswersU2',
                $this->weightedRatingSum('rateCommonAnswerU1', 'totalRatingCommonAnswersU1'),
                $this->weightedRatingSum('rateCommonAnswerU2', 'totalRatingCommonAnswersU2'))
            ->with('toFloat(numOfCommonQuestions) as numOfCommonQuestions',
                'toFloat(totalRatingAcceptedAnswersU1) AS totalRatingAcceptedAnswersU1',
                'toFloat(totalRatingAcceptedAnswersU2) AS totalRatingAcceptedAnswersU2',
                'toFloat(totalRatingCommonAnswersU1) AS totalRatingCommonAnswersU1',
                'toFloat(totalRatingCommonAnswersU2) AS totalRatingCommonAnswersU2')
            //'error' == max matching depending on common questions
            ->with(
                'CASE
                    WHEN numOfCommonQuestions > 0 THEN
                        1 - (1 / numOfCommonQuestions)
                    ELSE tofloat(0)
                END AS error',
                $this->rawMatching('totalRatingCommonAnswersU1',
                    'totalRatingAcceptedAnswersU1',
                    'rawMatchingU1'),
                $this->rawMatching('totalRatingCommonAnswersU2',
                    'totalRatingAcceptedAnswersU2',
                    'rawMatchingU2'))
            ->with('error',
                'tofloat( sqrt(rawMatchingU1 * rawMatchingU2)) AS rawMatching')
            ->with('CASE
		        WHEN error < rawMatching THEN error
		        ELSE rawMatching
                END AS matching')
            ->returns('matching');

        $result = $qb->getQuery()->getResultSet();

        $matching = 0;
        if ($result->count() == 1) {
            $matching = $result->current()->offsetGet('matching');
        }

        //Create the matching relationship with the appropriate value

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u1:User)', '(u2:User)')
            ->where('u1.qnoow_id = {id1}', 'u2.qnoow_id = {id2}')
            ->createUnique('(u1)-[m:MATCHES]-(u2)')
            ->set('m.matching_questions = {matching}', 'm.timestamp_questions = timestamp()')
            ->returns('m');

        //State the value of the variables in the query string
        $qb->setParameters(array(
            'id1' => (integer)$id1,
            'id2' => (integer)$id2,
            'matching' => (float)$matching
        ));

        $qb->getQuery()->getResultSet();

        return $matching == null ? 0 : $matching;
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