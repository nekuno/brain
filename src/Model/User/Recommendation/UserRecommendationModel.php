<?php

namespace Model\User\Recommendation;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Model\User\MatchingModel;

class UserRecommendationModel
{

    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @var \Model\User\MatchingModel
     */
    protected $matchingModel;

    /**
     * @param \Everyman\Neo4j\Client $client
     * @param \Model\User\MatchingModel $matchingModel
     */
    public function __construct(Client $client, MatchingModel $matchingModel)
    {

        $this->client = $client;
        $this->matchingModel = $matchingModel;
    }

    /**
     * Get top recommended users based on Answes to Questions
     *
     * @param    int $id id of the user
     * @throws \Exception
     * @return   array           ordered array of users
     */
    public function getUserRecommendationsBasedOnAnswers($id)
    {

        $query = "
            MATCH
            (u:User {qnoow_id: " . $id . "})
            MATCH
            (u)-[r:MATCHES]-(anyUser:User)
            WHERE r.matching_questions > 0
            WITH
            r.matching_questions AS m, anyUser.qnoow_id AS users, r
            RETURN
            users AS ids, m AS matchings_questions
            ORDER BY
            m DESC
            LIMIT 10
            ;
         ";

        //Create the Neo4j query object
        $topUsersQuery = new Query(
            $this->client,
            $query
        );

        //Execute query
        try {
            $topUsersResult = $topUsersQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        $response = array();
        foreach ($topUsersResult as $row) {
            $user = array(
                'id' => $row['ids'],
                'matching' => $row['matchings_questions'],
            );
            $response[] = $user;
        }

        return $response;
    }

    /**
     * Get top recommended users based on Answers to Questions
     *
     * @param    int $id id of the user
     * @throws \Exception
     * @return   array           ordered array of users
     */
    public function getUserRecommendationsBasedOnSharedContent($id)
    {

        $query = "
            MATCH
            (u:User {qnoow_id: " . $id . "})
            MATCH
            (u)-[r:MATCHES]-(anyUser:User)
            WHERE r.matching_content > 0
            WITH
            r.matching_content AS m, anyUser.qnoow_id AS users, r
            RETURN
            users AS ids, m AS matchings_content
            ORDER BY
            m DESC
            LIMIT 10
            ;
        ";

        //Create the Neo4j query object
        $topUsersQuery = new Query(
            $this->client,
            $query
        );

        //Execute query
        try {
            $topUsersResult = $topUsersQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        $response = array();
        foreach ($topUsersResult as $row) {
            $matching = $this->matchingModel->applyMatchingBasedOnContentCorrectionFactor($row['matchings_content']);
            $user = array(
                'id' => $row['ids'],
                'matching' => $matching,
            );
            $response[] = $user;
        }

        return $response;
    }
} 