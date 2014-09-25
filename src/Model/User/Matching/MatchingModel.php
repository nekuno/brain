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
     * @var average for the Normal Distribution. Average number of tags between any 2 users.
     */
    public $ave_content;

    /**
     * @var standard deviation of the Normal Distribution. Based on number of tags between users
     */
    public $stdev_content;

    /**
     * @var average for the Normal Distribution. Average number of answers answered by any user that are accepted by any other user
     */
    public $ave_questions;

    /**
     * @var standard deviation for the Normal Distribution. Based on the number of answers in common between users.
     */
    public $stdev_questions;

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
            a=(u1)-[rl1]->(cl1:Link:Content)-[:TAGGED]->(tl1:Tag)
        OPTIONAL MATCH
            b=(u2)-[rl2]->(cl2:Link:Content)-[:TAGGED]->(tl2:Tag)
        WHERE
            type(rl1) = type(rl2) AND
            tl1 = tl2 AND
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

        $this->ave_content = $average;
        $this->stdev_content = $stdev;
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

        //Set the average and standard deviation for the content Normal Distribution
        $this->ave_questions = $average;
        $this->stdev_questions = $stdev;
    }

    /**********************************************************************************************************************
     *
     */

} 