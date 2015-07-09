<?php

namespace Model\User;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class ContentTagModel
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
     * Get a list of recommended tag
     * @param $id
     * @param $startingWith
     * @param int $limit
     * @throws \Exception
     * @return array
     */
    public function getContentTags($id, $startingWith='', $limit=0)
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

        $query = "
            MATCH
            (u:User)
            WHERE u.qnoow_id = {UserId}
            MATCH
            (u)-[:LIKES]->(content:Link)
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