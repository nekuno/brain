<?php

namespace Model\User\Matching;

use Event\MatchingExpiredEvent;
use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
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
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

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
     * @param \Everyman\Neo4j\Client $client
     * @param \Model\User\ContentPaginatedModel $contentPaginatedModel
     * @param \Model\User\AnswerModel $answerModel
     */
    public function __construct(
        EventDispatcher $dispatcher,
        Client $client,
        ContentPaginatedModel $contentPaginatedModel,
        AnswerModel $answerModel
    ) {

        $this->dispatcher              = $dispatcher;
        $this->client                  = $client;
        $this->contentPaginatedModel   = $contentPaginatedModel;
        $this->answerModel             = $answerModel;
    }

    /**
     * @param $id
     * @return string
     * @throws \Exception
     */
    public function getPreferredMatchingType($id)
    {

        $numberOfSharedContent     = $this->contentPaginatedModel->countTotal(array('id' => $id));
        $numberOfAnsweredQuestions = $this->answerModel->countTotal(array('id' => $id));

        if ($numberOfSharedContent > (2 * $numberOfAnsweredQuestions)) {
            return self::PREFERRED_MATCHING_CONTENT;
        } else {
            return self::PREFERRED_MATCHING_ANSWERS;
        }
    }

    /**
     * @param $id1
     * @param $id2
     * @return array
     * @throws \Exception
     */
    public function getMatchingBetweenTwoUsers($id1, $id2)
    {

        $params = array(
            'id1' => (int)$id1,
            'id2' => (int)$id2,
        );

        //Check that both users have at least one url in common
        $query =
            "MATCH
                (u1:User {qnoow_id: {id1}}),
                (u2:User {qnoow_id: {id2}})
            OPTIONAL MATCH
                (u1)-[m:MATCHES]-(u2)
            RETURN
                m
            LIMIT 1
            ;";

        //Create the Neo4j query object
        $matchingQuery = new Query(
            $this->client,
            $query,
            $params
        );

        try {
            $result = $matchingQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        $haveMatching = false;

        foreach ($result as $row) {
            $haveMatching = true;
        }

        if ($haveMatching) {

            $matching = null;

            foreach ($result as $match) {
                if ($match['m']) {
                    $matching['matching_questions']  = $match['m']->getProperty('matching_questions') ?: 0;
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
            $matchingUpdatedAt    = $rawMatching[$tsIndex] ? $rawMatching[$tsIndex] : 0;
            $currentTimeInMillis  = time() * 1000;
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
            $event = new MatchingExpiredEvent($id1, $id2, 'answer');
            $this->dispatcher->dispatch(\AppEvents::USER_MATCHING_EXPIRED, $event);
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

        //Construct query String
        $queryString = "
        MATCH
            (u1:User {qnoow_id: 1}),
            (u2:User {qnoow_id: 2})
        OPTIONAL MATCH
            (u1)-[:ACCEPTS]->(acceptedAnswerU1:Answer)<-[:ANSWERS]-(u2),
            (acceptedAnswerU1)-[:IS_ANSWER_OF]->(:Question)<-[rateAcceptedAnswerU1:RATES]-(u1)
	    WITH
	        u1, u2, SUM(CASE toint(rateAcceptedAnswerU1.rating) WHEN 0 THEN 0 WHEN 1 THEN 1 WHEN 2 THEN 10 WHEN 3 THEN 50 ELSE 0 END) AS totalRatingAcceptedAnswersU1
        OPTIONAL MATCH
            (u2)-[:ACCEPTS]->(acceptedAnswerU2:Answer)<-[:ANSWERS]-(u1),
            (acceptedAnswerU2)-[:IS_ANSWER_OF]->(:Question)<-[rateAcceptedAnswerU2:RATES]-(u2)
	    WITH
	        u1, u2, totalRatingAcceptedAnswersU1, SUM(CASE toint(rateAcceptedAnswerU2.rating) WHEN 0 THEN 0 WHEN 1 THEN 1 WHEN 2 THEN 10 WHEN 3 THEN 50 ELSE 0 END) AS totalRatingAcceptedAnswersU2
        OPTIONAL MATCH
            (u1)-[rateCommonAnswerU1:RATES]->(commonQuestions:Question)<-[rateCommonAnswerU2:RATES]-(u2)
        WITH
            count(DISTINCT commonQuestions) AS numOfCommonQuestions, totalRatingAcceptedAnswersU1, totalRatingAcceptedAnswersU2,
	        SUM(CASE toint(rateCommonAnswerU1.rating) WHEN 0 THEN 0 WHEN 1 THEN 1 WHEN 2 THEN 10 WHEN 3 THEN 50 ELSE 0 END) AS totalRatingCommonAnswersU1,
	        SUM(CASE toint(rateCommonAnswerU2.rating) WHEN 0 THEN 0 WHEN 1 THEN 1 WHEN 2 THEN 10 WHEN 3 THEN 50 ELSE 0 END) AS totalRatingCommonAnswersU2
        WITH
            tofloat(numOfCommonQuestions) as numOfCommonQuestions,
	        toFloat(totalRatingAcceptedAnswersU1) AS totalRatingAcceptedAnswersU1,
            toFloat(totalRatingAcceptedAnswersU2) AS totalRatingAcceptedAnswersU2,
	        toFloat(totalRatingCommonAnswersU1) AS totalRatingCommonAnswersU1,
	        toFloat(totalRatingCommonAnswersU2) AS totalRatingCommonAnswersU2
        WITH
	        CASE
                WHEN numOfCommonQuestions > 0 THEN
	                1 - (1 / numOfCommonQuestions)
		        ELSE tofloat(0)
	        END AS error,
            CASE
                WHEN totalRatingCommonAnswersU1 > 0 AND totalRatingCommonAnswersU2 > 0 THEN
		            tofloat(
		                sqrt(
		                    (totalRatingAcceptedAnswersU1/totalRatingCommonAnswersU1) *
		                    (totalRatingAcceptedAnswersU2/totalRatingCommonAnswersU2)
                        )
                    )
                ELSE tofloat(0)
            END AS rawMatching
        WITH
	        CASE
		        WHEN error < rawMatching THEN error
		        ELSE rawMatching
            END AS matching
        RETURN
            matching
        ";

        //State the value of the variables in the query string
        $queryDataArray = array(
            'id1' => (integer)$id1,
            'id2' => (integer)$id2
        );

        //Construct query
        $query = new Query(
            $this->client,
            $queryString,
            $queryDataArray
        );

        //Execute query
        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        $matching = 0;
        foreach ($result as $row) {
            $matching = $row['matching'];
        }

        //Create the matching relationship with the appropriate value

        $queryString = "
        MATCH
            (u1:User),
            (u2:User)
        WHERE
            u1.qnoow_id = {id1} AND
            u2.qnoow_id = {id2}
        CREATE UNIQUE
            (u1)-[m:MATCHES]-(u2)
        SET
            m.matching_questions = {matching},
            m.timestamp_questions = timestamp()
        RETURN
            m
        ";

        //State the value of the variables in the query string
        $queryDataArray = array(
            'id1'      => (integer)$id1,
            'id2'      => (integer)$id2,
            'matching' => (float)$matching
        );

        //Construct query
        $query = new Query(
            $this->client,
            $queryString,
            $queryDataArray
        );

        //Execute query
        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        return $matching == null ? 0 : $matching;
    }

}