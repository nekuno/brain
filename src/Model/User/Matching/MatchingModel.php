<?php

namespace Model\User\Matching;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Model\User\ContentPaginatedModel;
use Model\User\AnswerModel;

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

    /**********************************************************************************************************************
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

    /************************************************************************************************************************
     * @param $id id of the user
     * @return string 'content' or 'answers' depending on which is the preferred matching type for the user
     */
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

    /************************************************************************************************************************
     *
     */
    public function updateContentNormalDistributionVariables()
    {
        //Construct query string
        $queryString = "
        MATCH
            (u1:User),
            (u2:User)
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
            u1, u2, length(collect(DISTINCT cl2)) AS numOfContentsInCommon
        RETURN
            avg(numOfContentsInCommon) AS ave_content,
            stdevp(numOfContentsInCommon) AS stdev_content
        ";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $queryString
        );

        //Execute Query
        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        //Get the wanted results
        foreach ($result as $row) {
            $average = $row['ave_content'];
            $stdev = $row['stdev_content'];
        }

        //Persist data

        $date = date("d-m-Y [H:i:s]");
        $data = array();
        $data = array("average" => $average, "stdev" => $stdev, "calculationDate" => $date );

        $dataDirectory = "matchingData";
        if(!file_exists($dataDirectory) && !is_dir($dataDirectory)){
            mkdir($dataDirectory);
        }

        $file = "contentNormalDistributionVariables.json";
        $log = "contentNormalDistributionVariables.log";

        if(file_exists($dataDirectory . "/" . $file)){
            file_put_contents( $dataDirectory . "/" . $log, file_get_contents($dataDirectory."/".$file)."\n", FILE_APPEND );
        }
        file_put_contents( $dataDirectory . "/" . $file, json_encode($data) );

        return $data;

    }

    public function updateQuestionsNormalDistributionVariables()
    {
        //Construct query string
        $queryString = "
        MATCH
            (u1:User),
            (u2:User)
        OPTIONAL MATCH
            (u1)-[:ACCEPTS]->(commonanswer:Answer)<-[:ANSWERS]-(u2)
        WITH
            u1, u2, count(commonanswer) AS numOfCommonAnswers
        RETURN
            avg(numOfCommonAnswers) AS ave_questions,
            stdevp(numOfCommonAnswers) AS stdev_questions
        ";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $queryString
        );

        //Execute Query
        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        //Get the wanted results
        foreach ($result as $row) {
            $average = $row['ave_questions'];
            $stdev = $row['stdev_questions'];
        }

        //Persist data

        $date = date("d-m-Y [H:i:s]");
        $data = array();
        $data = array("average" => $average, "stdev" => $stdev, "calculationDate" => $date );

        $dataDirectory = "matchingData";
        if(!file_exists($dataDirectory) && !is_dir($dataDirectory)){
            mkdir($dataDirectory);
        }

        $file = "questionsNormalDistributionVariables.json";
        $log = "questionsNormalDistributionVariables.log";

        if(file_exists($dataDirectory . "/" . $file)){
            file_put_contents( $dataDirectory . "/" . $log, file_get_contents($dataDirectory."/".$file)."\n", FILE_APPEND );
        }
        file_put_contents( $dataDirectory . "/" . $file, json_encode($data) );

        return $data;

    }

    /************************************************************************************************************
     * @param $id1 qnoow_id of the first user
     * @param $id2 qnoow_id of the second user
     * @return float matching by questions between both users
     * @throws \Exception
     */
    public function getMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2)
    {
        //Get the values of the parameters for the Normal distribution
        $dataDirectory = "matchingData";
        $file = "questionsNormalDistributionVariables.json";
        $data = json_decode(file_get_contents($dataDirectory."/".$file, true) ) ;

        $ave_questions = $data->average;
        $stdev_questions = $data->stdev;

        //Construct query String
        $queryString = "
        MATCH
            (u1:User),
            (u2:User)
        WHERE
            u1.qnoow_id = {id1} AND
            u2.qnoow_id = {id2}
        OPTIONAL MATCH
            (u1)-[:ACCEPTS]->(commonanswer1:Answer)<-[:ANSWERS]-(u2),
            (commonanswer1)-[:IS_ANSWER_OF]->(commonquestion1)<-[r1:RATES]-(u1)
        OPTIONAL MATCH
            (u2)-[:ACCEPTS]->(commonanswer2:Answer)<-[:ANSWERS]-(u1),
            (commonanswer2)-[:IS_ANSWER_OF]->(commonquestion2)<-[r2:RATES]-(u2)
        OPTIONAL MATCH
            (u1)-[r3:RATES]->(:Question)<-[r4:RATES]-(u2)
        WITH
            u1, u2,
            (count(commonanswer1)+count(commonanswer2))/2 AS numOfCommonAnswers,
            [n1 IN collect(distinct r1) |n1.rating] AS little1_elems,
            [n2 IN collect(distinct r2) |n2.rating] AS little2_elems,
            [n3 IN collect(distinct r3) |n3.rating] AS CIT1_elems,
            [n4 IN collect(distinct r4) |n4.rating] AS CIT2_elems
        WITH
            u1, u2, numOfCommonAnswers,
            tofloat( reduce(little1 = 0, n1 IN little1_elems | little1 + n1) ) AS little1,
            tofloat( reduce(little2 = 0, n2 IN little2_elems | little2 + n2) ) AS little2,
            tofloat( reduce(CIT1 = 0, n3 IN CIT1_elems | CIT1 + n3) ) AS CIT1,
            tofloat( reduce(CIT1 = 0, n4 IN CIT2_elems | CIT1 + n4) ) AS CIT2
        WITH
            u1, u2, numOfCommonAnswers,
            CASE
                WHEN
                    CIT1 > 0 AND CIT2 > 0
                THEN
                    sqrt( tofloat( little1/CIT1 ) * tofloat( little2/CIT2 ) )
                ELSE
                    0
            END
            AS match_user1_user2
        RETURN
            match_user1_user2,
            numOfCommonAnswers
        ";

        //State the value of the variables in the query string
        $queryDataArray = array(
            'id1' => $id1,
            'id2' => $id2
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
        foreach($result as $row){
            $ratingForMatching = $row['match_user1_user2'];
            $normal_x = $row['numOfCommonAnswers'];
        }

        //Calculate the matching
        $matching =
            (
                $ratingForMatching +
                stats_dens_normal($normal_x, $ave_questions, $stdev_questions) //function from stats PHP extension
            ) / 2;

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
            m.matching_questions = {matching},
            m.timestamp_questions = timestamp()
        RETURN
            m
        ";

        //State the value of the variables in the query string
        $queryDataArray = array(
            'id1' => $id1,
            'id2' => $id2,
            'matching' => $matching
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
        //For testing purposes
        //return $ratingForMatching . ', ' . $normal_x . ', ' . $ave_questions . ', ' . $stdev_questions;
    }

    /**
     * @param $id1 qnoow_id of the first user
     * @param $id2 qnoow_id of the second user
     * @return float matching by content (with tags) between both users
     * @throws \Exception
     */
    public function getMatchingBetweenTwoUsersBasedOnSharedContent ($id1, $id2)
    {
        //Get the values of the parameters for the Normal distribution
        $dataDirectory = "matchingData";
        $file = "contentNormalDistributionVariables.json";
        $data = json_decode(file_get_contents($dataDirectory."/".$file, true) ) ;

        $ave_content = $data->average;
        $stdev_content = $data->stdev;

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
            'id1' => $id1,
            'id2' => $id2
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
        foreach($result as $row){
            $normal_x = $row['numOfCommonAnswers'];
        }

        //Calculate the matching
        $matching = stats_dens_normal($normal_x, $ave_content, $stdev_content);

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
            'id1' => $id1,
            'id2' => $id2,
            'matching' => $matching
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
        //For testing purposes:
        //return $normal_x . ', ' . $ave_content . ', ' . $stdev_content;
    }

} 