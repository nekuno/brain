<?php

namespace Model\User;

use Paginator\PaginatedInterface;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class ContentPaginatedModel implements PaginatedInterface
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
        $response = array();

        $query = "
            MATCH
            (u:User)
            WHERE u.qnoow_id = {UserId}
            MATCH
            (u)-[r:LIKES|DISLIKES]->(content:Link)
            OPTIONAL MATCH
            (content)-[:TAGGED]->(tag:Tag)
            RETURN
            type(r) as type, content, collect(distinct tag.name) as tags
            SKIP {offset}
            LIMIT {limit}
            ;
         ";

        //Create the Neo4j query object
        $contentQuery = new Query(
            $this->client,
            $query,
            array(
                'UserId' => (integer)$filters['id'],
                'offset' => (integer)$offset,
                'limit' => (integer)$limit
            )
        );

        //Execute query
        try {
            $result = $contentQuery->getResultSet();

            foreach ($result as $row) {
                $content = array();
                $content['type'] = $row['type'];
                $content['url'] = $row['content']->getProperty('url');
                $content['title'] = $row['content']->getProperty('title');
                $content['description'] = $row['content']->getProperty('description');
                foreach ($row['tags'] as $tag) {
                    $content['tags'][] = $tag;
                }

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
        $count = 0;

        $query = "
            MATCH
            (u:User)
            WHERE u.qnoow_id = {UserId}
            MATCH
            (u)-[r:LIKES|DISLIKES]->(content:Link)
            RETURN
            count(r) as total
            ;
         ";

        //Create the Neo4j query object
        $contentQuery = new Query(
            $this->client,
            $query,
            array(
                'UserId' => (integer)$filters['id'],
            )
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
}