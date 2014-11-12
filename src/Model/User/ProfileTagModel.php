<?php

namespace Model\User;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class ProfileTagModel
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
     * @param $type
     * @param $startingWith
     * @param int $limit
     * @throws \Exception
     * @return array
     */
    public function getProfileTags($type, $startingWith='', $limit=0)
    {
        $response = array();

        $params = array();

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
            (tag:ProfileTag:".ucfirst($type).")
        ";
        $query .= $startingWithQuery;
        $query .= "
            RETURN
            distinct tag.name as name
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
                $response['items'][] = array('name' => $row['name']);
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $response;
    }
} 