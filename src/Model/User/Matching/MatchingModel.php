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
     * @var NormalDistributionModel
     */
    protected $normalDistributionModel;

    /**
     * @param EventDispatcher $dispatcher
     * @param \Everyman\Neo4j\Client $client
     * @param \Model\User\ContentPaginatedModel $contentPaginatedModel
     * @param \Model\User\AnswerModel $answerModel
     * @param NormalDistributionModel $normalDistributionModel
     */
    public function __construct(
        EventDispatcher $dispatcher,
        Client $client,
        ContentPaginatedModel $contentPaginatedModel,
        AnswerModel $answerModel,
        NormalDistributionModel $normalDistributionModel
    ) {

        $this->dispatcher              = $dispatcher;
        $this->client                  = $client;
        $this->contentPaginatedModel   = $contentPaginatedModel;
        $this->answerModel             = $answerModel;
        $this->normalDistributionModel = $normalDistributionModel;
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
     * @return int
     * @throws \Exception
     */
    public function getMatchingBetweenTwoUsersBasedOnContent($id1, $id2)
    {

        $response['matching'] = 0;

        if ($this->hasContentInCommon($id1, $id2)) {
            $rawMatching = $this->getMatchingBetweenTwoUsers($id1, $id2);

            if ($this->isNecessaryToRecalculateIt($rawMatching, 'timestamp_content', 'matching_content')) {
                $event = new MatchingExpiredEvent($id1, $id2, 'content');
                $this->dispatcher->dispatch(\AppEvents::USER_MATCHING_EXPIRED, $event);
            }

            $matching = $rawMatching['matching_content'] ? $rawMatching['matching_content'] : 0;

            $response['matching'] = $matching;
        }

        return $response;
    }

    /**
     * @param $id1
     * @param $id2
     * @return array
     * @throws \Exception
     */
    public function hasContentInCommon($id1, $id2)
    {

        $params = array(
            'id1' => (int)$id1,
            'id2' => (int)$id2
        );

        //Check that both users have at least one url in common
        $check =
            "MATCH
                (u1:User {qnoow_id: {id1}}),
                (u2:User {qnoow_id: {id2}})
            OPTIONAL MATCH
                (u1)-[:LIKES]->(l:Link)<-[:LIKES]-(u2)
            OPTIONAL MATCH
                (u1)-[:DISLIKES]->(d:Link)<-[:DISLIKES]-(u2)
            RETURN
                count(l) AS l,
                count(d) AS d;";

        //Create the Neo4j query object
        $checkQuery = new Query(
            $this->client,
            $check,
            $params
        );

        try {
            $checkResult = $checkQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        $checkValueLikes    = 0;
        $checkValueDislikes = 0;
        foreach ($checkResult as $checkRow) {
            $checkValueLikes    = $checkRow['l'];
            $checkValueDislikes = $checkRow['d'];
        }

        return ($checkValueLikes > 0) || ($checkValueDislikes > 0);
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
                    $matching['matching_content']    = $match['m']->getProperty('matching_content') ?: 0;
                    $matching['timestamp_content']   = $match['m']->getProperty('timestamp_content') ?: 0;
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
     * Get the maximum matching based on content
     */
    public function getMaxMatchingBasedOnContent()
    {

        $maxMatching = 0;

        $query = "
            MATCH
            ()-[match:MATCHES]-()
            WHERE
            has(match.matching_content)
            RETURN
            match.matching_content as max
            ORDER BY
            match.matching_content DESC
            LIMIT 1
            ;
            ";

        //Create the Neo4j query object
        $neoQuery = new Query(
            $this->client,
            $query
        );

        //Execute query
        try {
            $result = $neoQuery->getResultSet();

        } catch (\Exception $e) {
            throw $e;
        }

        foreach ($result as $row) {
            $maxMatching = $row['max'];
        }

        return $maxMatching;
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
     * @param $userId
     * @param array $questions
     * @throws \Exception
     */
    public function calculateMatchingOfUserByAnswersWhenNewQuestionsAreAnswered($userId, array $questions)
    {

        $data = array(
            'questions' => implode(',', $questions),
            'userId'    => (integer)$userId,
        );

        $template = "
        MATCH
        (u:User)-[:RATES]->(q:Question)
        WHERE
        id(q) IN [ { questions } ]
        AND NOT u.qnoow_id = { userId }
        RETURN
        u.qnoow_id AS u;";

        //Create the Neo4j template object
        $query = new Query(
            $this->client,
            $template,
            $data
        );

        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        foreach ($result as $row) {
            $this->calculateMatchingBetweenTwoUsersBasedOnAnswers($userId, $row['u']);
        }
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
            (u1:User),
            (u2:User)
        WHERE
            u1.qnoow_id = {id1} AND
            u2.qnoow_id = {id2}
        OPTIONAL MATCH
            (u1)-[:ACCEPTS]->(acceptedAnswerU1:Answer)<-[:ANSWERS]-(u2),
            (acceptedAnswerU1)-[:IS_ANSWER_OF]->(:Question)<-[rateAcceptedAnswerU1:RATES]-(u1)
        OPTIONAL MATCH
            (u2)-[:ACCEPTS]->(acceptedAnswerU2:Answer)<-[:ANSWERS]-(u1),
            (acceptedAnswerU2)-[:IS_ANSWER_OF]->(:Question)<-[rateAcceptedAnswerU2:RATES]-(u2)
        OPTIONAL MATCH
            (u1)-[rateCommonAnswerU1:RATES]->(commonQuestions:Question)<-[rateCommonAnswerU2:RATES]-(u2)
        WITH
            count(DISTINCT commonQuestions) AS numOfCommonQuestions,
            [n1 IN collect(distinct rateAcceptedAnswerU1) |
                CASE toint(n1.rating)
			        WHEN 0 THEN 0
			        WHEN 1 THEN 1
			        WHEN 2 THEN 10
			        WHEN 3 THEN 50
			        ELSE 0
		        END
            ] AS arrayRatingAcceptedAnswerU1,
            [n2 IN collect(distinct rateAcceptedAnswerU2) |
                CASE toint(n2.rating)
			        WHEN 0 THEN 0
			        WHEN 1 THEN 1
			        WHEN 2 THEN 10
			        WHEN 3 THEN 50
			        ELSE 0
                END
            ] AS arrayRatingAcceptedAnswerU2,
            [n3 IN collect(distinct rateCommonAnswerU1) |
                CASE toint(n3.rating)
			        WHEN 0 THEN 0
			        WHEN 1 THEN 1
			        WHEN 2 THEN 10
			        WHEN 3 THEN 50
			        ELSE 0
                END
            ] AS arrayRatingCommonAnswerU1,
            [n4 IN collect(distinct rateCommonAnswerU2) |
                CASE toint(n4.rating)
			        WHEN 0 THEN 0
			        WHEN 1 THEN 1
			        WHEN 2 THEN 10
			        WHEN 3 THEN 50
			        ELSE 0
                END
            ] AS arrayRatingCommonAnswerU2
        WITH
            tofloat(numOfCommonQuestions) as numOfCommonQuestions,
            tofloat(
                reduce(
                    totalRatingAcceptedAnswersU1 = 0,
                    n1 IN arrayRatingAcceptedAnswerU1 | totalRatingAcceptedAnswersU1 + n1
                )
            ) AS totalRatingAcceptedAnswersU1,
            tofloat(
                reduce(
                    totalRatingAcceptedAnswersU2 = 0,
                    n2 IN arrayRatingAcceptedAnswerU2 | totalRatingAcceptedAnswersU2 + n2
                )
            ) AS totalRatingAcceptedAnswersU2,
            tofloat(
                reduce(
                    totalRatingCommonAnswersU1 = 0,
                    n3 IN arrayRatingCommonAnswerU1 | totalRatingCommonAnswersU1 + n3
                )
            ) AS totalRatingCommonAnswersU1,
            tofloat(
                reduce(
                    totalRatingCommonAnswersU2 = 0,
                    n4 IN arrayRatingCommonAnswerU2 | totalRatingCommonAnswersU2 + n4
                )
            ) AS totalRatingCommonAnswersU2
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

    /**
     * @param $id
     * @throws \Exception
     */
    public function calculateMatchingByContentOfUserWhenNewContentIsAdded($id)
    {

        //This query is for version 1.1 of the algorithm, which is the one currently being used in Brain
        //For
        $query = "
        MATCH
        (u:User {qnoow_id: " . $id . " })-[:LIKES|DISLIKES]->(:Link)<-[:LIKES|DISLIKES]-(v:User)
        RETURN
        v.qnoow_id AS id;";

        //Create the Neo4j query object
        $neoQuery = new Query(
            $this->client,
            $query
        );

        try {
            $result = $neoQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        foreach ($result as $row) {
            $this->calculateMatchingBetweenTwoUsersBasedOnSharedContent($id, $row['id']);
        }
    }

    /**
     * @param $id1 qnoow_id of the first user
     * @param $id2 qnoow_id of the second user
     * @return float matching by content (with tags) between both users
     * @throws \Exception
     */
    public function calculateMatchingBetweenTwoUsersBasedOnSharedContent($id1, $id2)
    {

        $data = $this->normalDistributionModel->getContentNormalDistributionVariables();

        $contentAverage = $data->average;
        $contentStdev   = $data->stdev;

        //Construct query String
        $queryString = "
        MATCH
            (u1:User),
            (u2:User)
        WHERE
            u1.qnoow_id = {id1} AND
            u2.qnoow_id = {id2}
        OPTIONAL MATCH
            a=(u1)-[rl1]->(cl1:Link)-[:TAGGED]->(tl1:Tag)
        OPTIONAL MATCH
            b=(u2)-[rl2]->(cl2:Link)-[:TAGGED]->(tl2:Tag)
        WHERE
                type(rl1) = type(rl2)
            AND
                tl1 = tl2
            AND
                (cl1 = cl2 OR cl1 <> cl2)
        WITH
            count(DISTINCT cl2) AS numOfContentsInCommon
        RETURN
            numOfContentsInCommon AS numOfCommonContent
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

        //Get the wanted results
        $normalX = 0;
        foreach ($result as $row) {
            $normalX = $row['numOfCommonContent'];
        }

        //Calculate the matching
        $matching = stats_dens_normal($normalX, $contentAverage, $contentStdev);

        if ($matching == false) {
            $matching = 0;
        }

        //Query to create the matching relationship with the appropriate value

        //Construct query String
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
            m.matching_content = {matching},
            m.timestamp_content = timestamp()
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

        return $matching;
    }

}