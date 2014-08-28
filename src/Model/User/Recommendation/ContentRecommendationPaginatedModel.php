<?php

namespace Model\User\Recommendation;

use Paginator\PaginatedInterface;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class ContentRecommendationPaginatedModel implements PaginatedInterface
{
    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @param \Everyman\Neo4j\Client $client
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        return isset($filters['id']);
    }

    /**
     * Slices the query according to $offset, and $limit.
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @throws \Exception
     * @return array
     */
    public function slice(array $filters, $offset, $limit)
    {
        $id = $filters['id'];
        $response = array();

        $params = array(
            'UserId' => (integer)$id,
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        if (isset($filters['tag'])) {
            $whereTags = "
                MATCH
                (content)-[:TAGGED]->(filterTag:Tag)
                WHERE filterTag.name = {tag}
                OPTIONAL MATCH
                (content)-[:TAGGED]->(tag:Tag)
            ";
            $params['tag'] = $filters['tag'];
        } else {
            $whereTags = "
                OPTIONAL MATCH
                (content)-[:TAGGED]->(tag:Tag)
            ";
        }

        if($this->getNumberOfSharedContent($id) > (2 * $this->getNumberOfAnsweredQuestions($id)) ){
            $query = "
                MATCH
                (user:User {qnoow_id: {UserId}})-[match:MATCHES]-(matching_users:User)
                WHERE
                has(match.matching_content)
                MATCH
                (matching_users)-[:LIKES]->(content:Link)
                WHERE
                NOT (user)-[:LIKES]->(content)
            ";
            $query .= $whereTags;
            $query .= "
                RETURN
                content,
                match.matching_content AS match,
                matching_users AS via,
                collect(distinct tag.name) as tags
                ORDER BY
                match
                SKIP {offset}
                LIMIT {limit};
            ";
        } else {
            $query = "
                MATCH
                (user:User {qnoow_id: {UserId})-[match:MATCHES]-(matching_users:User)
                WHERE
                has(match.matching_questions)
                MATCH
                (matching_users)-[:LIKES]->(content:Link)
                WHERE
                NOT (user)-[:LIKES]->(content)
            ";
            $query .= $whereTags;
            $query .= "
                RETURN
                content,
                match.matching_content AS match,
                matching_users AS via,
                collect(distinct tag.name) as tags
                ORDER BY
                match;
                SKIP {offset}
                LIMIT {limit}
            ";
        }

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
                $content['url'] = $row['content']->getProperty('url');
                $content['title'] = $row['content']->getProperty('title');
                $content['description'] = $row['content']->getProperty('description');
                foreach ($row['tags'] as $tag) {
                    $content['tags'][] = $tag;
                }
                $content['match'] = $row['match'];
                $content['via']['qnoow_id'] = $row['via']->getProperty('qnoow_id');
                $content['via']['name'] = $row['via']->getProperty('username');

                $response[] = $content;
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $response;
    }

    /**
     * Counts the total results from queryset.
     * @param array $filters
     * @throws \Exception
     * @return int
     */
    public function countTotal(array $filters)
    {
        $id = $filters['id'];
        $count = 0;

        $params = array(
            'UserId' => (integer)$id,
        );

        if (isset($filters['tag'])) {
            $whereTags = "
                MATCH
                (content)-[:TAGGED]->(tag:Tag)
                WHERE tag.name = {tag}
            ";
            $params['tag'] = $filters['tag'];
        } else {
            $whereTags = " ";
        }

        if($this->getNumberOfSharedContent($id) > (2 * $this->getNumberOfAnsweredQuestions($id)) ){
            $query = "
                MATCH
                (user:User {qnoow_id: {UserId}})-[match:MATCHES]-(matching_users:User)
                WHERE
                has(match.matching_content)
                MATCH
                (matching_users)-[r:LIKES]->(content:Link)
                WHERE
                NOT (user)-[:LIKES]->(content)
            ";
            $query .= $whereTags;
            $query .= "
                RETURN
                count(distinct r) as total;
            ";
        } else {
            $query = "
                MATCH
                (user:User {qnoow_id: {UserId})-[match:MATCHES]-(matching_users:User)
                WHERE
                has(match.matching_questions)
                MATCH
                (matching_users)-[r:LIKES]->(content:Link)
                WHERE
                NOT (user)-[:LIKES]->(content)
            ";
            $query .= $whereTags;
            $query .= "
                RETURN
                count(distinct r) as total;
            ";
        }

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
                $count = $row['total'];
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $count;
    }

    /**
     * @param $id id of the user for which we want to know how many questions he or she has answered
     * @throws \Exception
     * @return int
     */
    private function getNumberOfAnsweredQuestions($id){

        $query = "
            MATCH
            (u:User {qnoow_id: " . $id . "})-[r:RATES]->(q:Question)
            RETURN
            count(distinct r) AS quantity;
        ";

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

        $numberOfAnsweredQuestions = 0;
        foreach ($result as $row)  {
            $numberOfAnsweredQuestions = $row['quantity'];
        }

        return $numberOfAnsweredQuestions;
    }

    /**
     * @param $id id of the user for which we want to know how many contents he or she has shared
     * @throws \Exception
     * @return int
     */
    private function  getNumberOfSharedContent($id){

        $query = "
            MATCH
            (u:User {qnoow_id: " . $id . "})-[r:LIKES|DISLIKES]->(q:Link)
            RETURN
            count(distinct r) AS quantity;
        ";

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

        $numberOfSharedQuestions = 0;
        foreach ($result as $row)  {
            $numberOfSharedQuestions = $row['quantity'];
        }

        return $numberOfSharedQuestions;
    }
} 