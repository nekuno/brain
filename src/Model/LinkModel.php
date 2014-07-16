<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/17/14
 * Time: 6:44 PM
 */

namespace Model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class LinkModel
{

    protected $client;

    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    public function addLink(array $data)
    {

        $duplicate = $this->getDuplicate($data['url']);

        if (array() === $duplicate) {
            $stringQuery = "MATCH (u:User) " .
                "WHERE u.qnoow_id = {userId}"
                . " CREATE "
                . " (l:Link {url: {url}, title: {title}, description: {description}})"
                . ", (l)-[r:LIKES]->(u) "
                . " RETURN l;";
        } else {
            $stringQuery = "MATCH (u:User)" .
                ", (l:Link) "
                . " WHERE u.qnoow_id = {userId} AND l.url = {url}"
                . " CREATE UNIQUE (l)-[r:LIKES]->(u)"
                . " RETURN l;
            ";
        }

        $query = new Query(
            $this->client,
            $stringQuery,
            array(
                'title'       => $data['title'],
                'description' => $data['description'],
                'url'         => $data['url'],
                'userId'      => $data['userId']
            )
        );

        try {
            $resultSet = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        return $resultSet;

    }

    private function getDuplicate($url)
    {

        $stringQuery = "
            MATCH
                (l:Link)-[r:LIKES]->(u)
            WHERE l.url = {url}
            RETURN l, u
            LIMIT 1";

        $query = new Query(
            $this->client,
            $stringQuery,
            array('url' => $url)
        );

        $result = $query->getResultSet();

        $duplicates = array();

        foreach ($result as $row) {
            $duplicate            = array();
            $duplicate['userId']  = $row['u']->getProperty('qnoow_id');
            $duplicate['linkUrl'] = $row['l']->getProperty('url');
            $duplicates[]         = $duplicate;
        }

        if (count($duplicates) > 0) {
            return $duplicates[0];
        }

        return $duplicates;

    }
}
