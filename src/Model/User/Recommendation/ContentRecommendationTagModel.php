<?php

namespace Model\User\Recommendation;

use Model\User\MatchingModel;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class ContentRecommendationTagModel
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
            $params['tag'] = $startingWith.".*";
            $startingWithQuery = 'WHERE tag.name =~ {tag}';
        }

        $limitQuery = '';
        if ($limit != 0) {
            $params['limit'] = (integer)$limit;
            $limitQuery = ' LIMIT {limit}';
        }

        $typeQuery = 'has(match.matching_questions)';
        if($this->matchingModel->getPreferredMatchingType($id) == MatchingModel::PREFERRED_MATCHING_CONTENT) {
            $typeQuery = "has(match.matching_content)";
        }

        $query = "
            MATCH
            (user:User {qnoow_id: {UserId}})-[match:MATCHES]-(matching_users:User)
            WHERE
        ";
        $query .= $typeQuery;
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