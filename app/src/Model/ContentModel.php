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
use Everyman\Neo4j\Query\ResultSet;

class ContentModel {

    protected $client;

    public function __construct(Client $client){
        $this->client = $client;
    }

    public function addLink(array $data)
    {


        $duplicated = $this->isDuplicatedLink($data);

        if($duplicated !== 0) {
            return new ResultSet($this->client, array());
        }

        $stringQuery = "
            MATCH (u:User {qnoow_id: " . $data['userId'] . "})
            CREATE
                (l:Link
                    {
                        url: '" . $data['url'] . "'
                        , title: '" . $data['title'] . "'
                        , description: '" . $data['description'] . "'
                    }
                ),
                (l)-[r:SHARED_BY]->(u)
            RETURN l;";

        $query = new Query(
            $this->client,
            $stringQuery
        );

        $result = array();

        foreach ($query->getResultSet() as $row) {
            $link['url'] = $row['l']->getProperty('url');
            $link['title'] = $row['l']->getProperty('title');
            $link['description'] = $row['l']->getProperty('description');
            $result[] = $link;
        }

        return $result;
    }

    private function isDuplicatedLink(array $data){

        $stringQuery = "
            MATCH
                (l:Link {url: '" . $data['url'] . "'})-[r:SHARED_BY]->(u:User {qnoow_id: '" . $data['userId'] . "'})
            RETURN r;";

        $query = new Query(
            $this->client,
            $stringQuery
        );

        $result = $query->getResultSet();

        return $result->count();
    }

} 