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


        // Check if exists other link with the same url and shared by the same user
        $duplicated = $this->isDuplicatedLink($data);
        if($duplicated !== 0) {
            return array();
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
                (l:Link {url: '" . $data['url'] . "'})-[r:SHARED_BY]->(u:User {qnoow_id: " . $data['userId'] . "})
            RETURN
                count(l) AS result;";

        $query = new Query(
            $this->client,
            $stringQuery
        );

        $result = $query->getResultSet();

        $numberOfShares = 0;
        foreach($result as $row){
            $numberOfShares = $row['result'];
        }

        return $numberOfShares;
    }

} 