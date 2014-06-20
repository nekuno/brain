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

class ContentModel
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
                "WHERE u.qnoow_id = " . $data['userId'] .
                "CREATE (l:Link {url: '" . $data['url'] . "', title: '" . $data['title'] . "', description: '" . $data['description'] . "'})" .
                ", (l)-[r:SHARED_BY]->(u) " .
                "RETURN l;";
        } else {
            $stringQuery = "MATCH (u:User)" .
                ", (l:Link) " .
                "WHERE u.qnoow_id = " . $data['userId'] . " AND l.url = '" . $data['userId'] . "'" .
                "CREATE UNIQUE (l)-[r:SHARED_BY]->(u) " .
                "RETURN l;
            ";
        }

        $query = new Query(
            $this->client,
            $stringQuery
        );

        $resultSet = $query->getResultSet();

        foreach ($resultSet as $row) {
            $link = array();
            $link['url']         = $row['l']->getProperty('url');
            $link['title']       = $row['l']->getProperty('title');
            $link['description'] = $row['l']->getProperty('description');
            $result[]            = $link;
        }

        if(isset($result)){
            return $result;
        }

    }

    private function getDuplicate($url)
    {

        $stringQuery = "
            MATCH
                (l:Link)-[r:SHARED_BY]->(u)
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
            $duplicate = array();
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