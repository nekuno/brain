<?php

namespace Model\User;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class MatchingModel
{
    const PREFERRED_MATCHING_CONTENT='content';
    const PREFERRED_MATCHING_ANSWERS='answers';

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
     * @param \Everyman\Neo4j\Client $client
     * @param \Model\User\ContentPaginatedModel $contentPaginatedModel
     * @param \Model\User\AnswerModel $answerModel
     */
    public function __construct(Client $client, ContentPaginatedModel $contentPaginatedModel, AnswerModel $answerModel)
    {
        $this->client = $client;
        $this->contentPaginatedModel = $contentPaginatedModel;
        $this->answerModel = $answerModel;
    }

    public function getPreferredMatchingType($id)
    {
        $numberOfSharedContent = $this->contentPaginatedModel->countTotal(array('id' => $id));
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
    public function getMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2)
    {
        $response = array();

        //Check that both users have at least one question in common
        $check =
            "MATCH
                (u1:User {qnoow_id: " . $id1 . "}),
                (u2:User {qnoow_id: " . $id2 . "})
            OPTIONAL MATCH
                (u1)-[:RATES]->(commonquestion:Question)<-[:RATES]-(u2)
            RETURN
                count(distinct commonquestion) AS n;";

        //Create the Neo4j query object
        $checkQuery = new Query(
            $this->client,
            $check
        );

        try {
            $checkResult = $checkQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        $checkValue = 0;
        foreach ($checkResult as $checkRow) {
            $checkValue = $checkRow['n'];
        }

        if ($checkValue > 0) {

            //Construct the query string
            $query =
                "MATCH
                    (u1:User {qnoow_id: " . $id1 . "}),
                    (u2:User {qnoow_id: " . $id2 . "})
                OPTIONAL MATCH
                    (u1)-[:ACCEPTS]->(commonanswer1:Answer)<-[:ANSWERS]-(u2),
                    (commonanswer1)-[:IS_ANSWER_OF]->(commonquestion1)<-[r1:RATES]-(u1)
                OPTIONAL MATCH
                    (u2)-[:ACCEPTS]->(commonanswer2:Answer)<-[:ANSWERS]-(u1),
                    (commonanswer2)-[:IS_ANSWER_OF]->(commonquestion2)<-[r2:RATES]-(u2)
                OPTIONAL MATCH
                    (u1)-[r3:RATES]->(:Question)<-[r4:RATES]-(u2)
                WITH
                    [n1 IN collect(distinct r1) |n1.rating] AS little1_elems,
                    [n2 IN collect(distinct r2) |n2.rating] AS little2_elems,
                    [n3 IN collect(distinct r3) |n3.rating] AS CIT1_elems,
                    [n4 IN collect(distinct r4) |n4.rating] AS CIT2_elems
                WITH
                    reduce(little1 = 0, n1 IN little1_elems | little1 + n1) AS little1,
                    reduce(little2 = 0, n2 IN little2_elems | little2 + n2) AS little2,
                    reduce(CIT1 = 0, n3 IN CIT1_elems | CIT1 + n3) AS CIT1,
                    reduce(CIT1 = 0, n4 IN CIT2_elems | CIT1 + n4) AS CIT2
                WITH
                    sqrt( (little1*1.0/CIT1) * (little2*1.0/CIT2) ) AS match_user1_user2
                MATCH
                    (u1:User {qnoow_id: " . $id1 . "}),
                    (u2:User {qnoow_id: " . $id2 . "})
                CREATE UNIQUE
                    (u1)-[m:MATCHES]-(u2)
                SET
                    m.matching_questions = match_user1_user2,
                    m.timestamp_questions = timestamp()
                RETURN
                    m;";

            //Create the Neo4j query object
            $neoQuery = new Query(
                $this->client,
                $query
            );

            //Execute query and get the return
            try {
                $result = $neoQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;
            }

            foreach ($result as $row) {
                $response['matching'] = $row['m']->getProperty('matching_questions');
            }
        } else {
            $response['matching'] = 0;
        }

        return $response;

    }

    /**
     * @param $id1
     * @param $id2
     * @return array
     * @throws \Exception
     */
    public function getMatchingBetweenTwoUsersBasedOnSharedContent($id1, $id2)
    {
        $response = array();

        //Check that both users have at least one url in common
        $check =
            "MATCH
                (u1:User {qnoow_id: " . $id1 . "}),
                (u2:User {qnoow_id: " . $id2 . "})
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
            $check
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

        if ($checkValueLikes > 0 || $checkValueDislikes > 0) {
            $query = "
                MATCH
                (u:User)-[r:LIKES|DISLIKES]->(l:Link)
                WITH
                l, count(distinct r) AS num_likes_dislikes
                ORDER BY num_likes_dislikes DESC
                WITH
                collect(num_likes_dislikes)[0] AS max_popul
                MATCH
                (u1:User {qnoow_id: ".$id1."}),
                (u2:User {qnoow_id: ".$id2."})
                OPTIONAL MATCH
                (u1)-[:LIKES]->(common_likes:Link)<-[:LIKES]-(u2)
                OPTIONAL MATCH
                (u1)-[:DISLIKES]->(common_dislikes:Link)<-[:DISLIKES]-(u2)
                WITH
                collect(distinct common_likes) + collect(distinct common_dislikes) AS common,
                max_popul, u1, u2
                OPTIONAL MATCH
                (u1)-[:LIKES|DISLIKES]->(c1:Link)
                WHERE
                NOT c1 IN common
                OPTIONAL MATCH
                (u2)-[:LIKES|DISLIKES]->(c2:Link)
                WHERE
                NOT c2 IN common
                WITH
                collect(distinct c1) AS c1,
                collect(distinct c2) AS c2,
                max_popul, common
                OPTIONAL MATCH
                (:User)-[r1:LIKES|DISLIKES]->(common_nodes)
                WHERE
                common_nodes IN common
                OPTIONAL MATCH
                (:User)-[r2:LIKES|DISLIKES]->(c1_nodes)
                WHERE
                c1_nodes IN c1
                OPTIONAL MATCH
                (:User)-[r3:LIKES|DISLIKES]->(c2_nodes)
                WHERE
                c2_nodes IN c2
                WITH
                count(distinct r1) AS popul_common,
                count(distinct r2) AS popul_c1,
                count(distinct r3) AS popul_c2,
                common_nodes, c1_nodes, c2_nodes, max_popul
                WITH
                common_nodes, collect(popul_common) AS p_common_coll,
                c1_nodes, collect(popul_c1) AS p_c1_coll,
                c2_nodes, collect(popul_c2) AS p_c2_coll,
                max_popul
                WITH
                reduce(num = 0.0, a IN p_common_coll | num + (1 - (a*1.0 / (max_popul+0.1)))^3 ) as dividend,
                reduce(num = 0.0, b IN p_c1_coll | num + ( (b*1.0 / max_popul + 0.1))^3 ) as divisor1,
                reduce(num = 0.0, c IN p_c2_coll | num + ( (c*1.0 / max_popul + 0.1))^3 ) as divisor2
                RETURN
                DISTINCT dividend, divisor1, divisor2
            ";

            //Create the Neo4j query object
            $neoQuery = new Query(
                $this->client,
                $query
            );

            //Execute query and get the return
            try {
                $result = $neoQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;
            }

            foreach ($result as $row) {
                $unpopularityOfCommonContent = $row['dividend'];
                $popularityOfUser1ExclusiveContent = $row['divisor1'];
                $popularityOfUser2ExclusiveContent = $row['divisor2'];
            }

            $matchingValue = sqrt(
                pow($unpopularityOfCommonContent,2) /
                (
                    ($unpopularityOfCommonContent + $popularityOfUser1ExclusiveContent)
                    *
                    ($unpopularityOfCommonContent + $popularityOfUser2ExclusiveContent)
                )
            );

            $response['matching'] = $matchingValue;

            //Construct query to store matching
            $match = "
                MATCH
                    (u1:User {qnoow_id: " . $id1 . "}),
                    (u2:User {qnoow_id: " . $id2 . "})
                CREATE UNIQUE
                    (u1)-[m:MATCHES]-(u2)
                SET
                    m.matching_content = " . $matchingValue . " ,
                    m.timestamp_content = timestamp()
                RETURN
                    m;
            ";

            //Create the Neo4j query object
            $matchQuery = new Query(
                $this->client,
                $match
            );

            //Execute query
            try {
                $matchResult = $matchQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;
            }

        } else {
            $response['matching'] = 0;
        }

        if ($response['matching'] != 0) {
            $maxMatching = $this->getMaxMatchingBasedOnContent();

            $response['matching'] /= $maxMatching;
        }

        return $response;
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
     * Calculate matchings of the user with any other user, based on Answers to Questions
     *
     * @param   int $id id of the user
     */
    public function calculateAllMatchingsBasedOnAnswers($id)
    {
        $users = $this->getAllUserIdsExceptTheOneOfTheUser($id);

        foreach ($users as $u){
            $this->getMatchingBetweenTwoUsersBasedOnAnswers($id, $u);
        }
    }

    /**
     * Calculate matchings of the user with any other user, based on shared Content
     *
     * @param   int $id id of the user
     */
    public function calculateAllMatchingsBasedOnContent($id)
    {
        $users = $this->getAllUserIdsExceptTheOneOfTheUser($id);

        foreach ($users as $u){
            $this->getMatchingBetweenTwoUsersBasedOnSharedContent($id, $u);
        }
    }

    protected function getAllUserIdsExceptTheOneOfTheUser($id)
    {
        $query = "
            MATCH
            (u:User)
            WHERE
            NOT(u.qnoow_id = " . $id . ")
            RETURN
            collect(u.qnoow_id) AS ids;
        ";

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
            $response = $row['ids'];
        }

        return $response;
    }
} 