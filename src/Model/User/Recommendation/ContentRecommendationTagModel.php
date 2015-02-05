<?php

namespace Model\User\Recommendation;

use Model\User\Matching\MatchingModel;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class ContentRecommendationTagModel
{
    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @var MatchingModel
     */
    protected $matchingModel;

    /**
     * @param \Everyman\Neo4j\Client $client
     * @param MatchingModel $matchingModel
     */
    public function __construct(Client $client, MatchingModel $matchingModel)
    {
        $this->client = $client;
        $this->matchingModel = $matchingModel;
    }

    /**
     * Get a list of recommended tag
     * @param $id
     * @param $startingWith
     * @param int $limit
     * @throws \Exception
     * @return array
     */
    public function getRecommendedTags($id, $startingWith='', $limit=0)
    {
        $response = array();

        $params = array('UserId' => (integer)$id);

        $startingWithQuery = '';
        if ($startingWith != '') {
            $params['tag'] = '(?i)'.$startingWith.'.*';
            $startingWithQuery = 'WHERE tag.name =~ {tag}';
        }

        $limitQuery = '';
        if ($limit != 0) {
            $params['limit'] = (integer)$limit;
            $limitQuery = ' LIMIT {limit}';
        }

        $preferredMatching = $this->matchingModel->getPreferredMatchingType($id);

        if($preferredMatching == MatchingModel::PREFERRED_MATCHING_CONTENT) {
            $query = "
                MATCH
                (user:User {qnoow_id: {UserId}})-[match:SIMILARITY]-(matching_users:User)
                WHERE has(match.similarity)
            ";
        } else {
            $query = "
                MATCH
                (user:User {qnoow_id: {UserId}})-[match:MATCHES]-(matching_users:User)
                WHERE has(match.matching_questions)
            ";
        }

        $query .= "
            MATCH
            (matching_users)-[:LIKES]->(content:Link)
            WHERE
            NOT (user)-[:LIKES]->(content)
            MATCH
            (content)-[r:TAGGED]->(tag:Tag)
        ";
        $query .= $startingWithQuery;
        $query .= "
            RETURN
            distinct tag.name as name, count(distinct r) as total
            ORDER BY
            tag.name
        ";
        $query .= $limitQuery;

        //Create the Neo4j query object
        $contentQuery = new Query(
            $this->client,
            $query,
            $params
        );

        //Execute query
        try {
            $result = $contentQuery->getResultSet();

            foreach ($result as $row) {
                $content = array();
                $content['name'] = $row['name'];
                $content['count'] = $row['total'];

                $response['items'][] = $content;
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $response;
    }
} 