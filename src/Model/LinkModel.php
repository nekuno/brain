<?php

namespace Model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

/**
 * Class LinkModel
 *
 * @package Model
 */
class LinkModel
{

    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     * @throws \Exception
     */
    public function addLink(array $data)
    {

        if (false === $this->isAlreadySaved($data['url'])) {
            $template = "MATCH (u:User)"
                . " WHERE u.qnoow_id = {userId}"
                . " CREATE "
                . " (l:Link {url: {url}, title: {title}, description: {description}, processed: 0})"
                . ", (u)-[r:LIKES]->(l) "
                . " RETURN l;";
        } else {
            $template = "MATCH (u:User)"
                . ", (l:Link) "
                . " WHERE u.qnoow_id = {userId} AND l.url = {url}"
                . " CREATE UNIQUE (u)-[r:LIKES]->(l)"
                . " RETURN l;
            ";
        }

        $query = new Query(
            $this->client,
            $template,
            array(
                'title'       => $data['title'],
                'description' => $data['description'],
                'url'         => $data['url'],
                'userId'      => (integer)$data['userId']
            )
        );

        return $query->getResultSet();
    }

    /**
     * @param $url
     * @return array
     */
    private function isAlreadySaved($url)
    {

        $template = "
            MATCH
                (u:User)-[:LIKES]->(l:Link)
            WHERE l.url = {url}
            RETURN l, u
            LIMIT 1";

        $query = new Query(
            $this->client,
            $template,
            array('url' => $url)
        );

        $result = $query->getResultSet();

        foreach ($result as $row) {
            return true;
        }

        return false;
    }

    public function updateLink(array $data, $processed = false)
    {

        $template = "MATCH (link:Link)"
            . " WHERE link.url = { tempId } "
            . " SET link.url = { url }"
            . " , link.title = { title }"
            . " , link.description = { description }"
            . " , link.processed = " . (integer)$processed
            . " RETURN link;";

        $query = new Query($this->client, $template, $data);

        try {
            return $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function createTag(array $tag)
    {

        $template = "MATCH (tag:Tag) WHERE tag.name = { name } RETURN tag LIMIT 1";

        $query = new Query($this->client, $template, $tag);

        $result = $query->getResultSet();

        foreach ($result as $row) {
            return $result;
        }

        $template = "CREATE (tag:Tag)"
            . "SET tag.name = { name }"
            . "RETURN tag";

        $query = new Query($this->client, $template, $tag);

        return $query->getResultSet();

    }

    public function addTag($link, $tag)
    {

        $template = "MATCH (link:Link)"
            . ", (tag:Tag)"
            . " WHERE link.url = { url } AND tag.name = { tag }"
            . " CREATE (link)-[:TAGGED]->(tag)";

        $params = array(
            'url' => $link['url'],
            'tag' => $tag['name'],
        );
        $query  = new Query($this->client, $template, $params);

        return $query->getResultSet();

    }

    public function addMultipleLinks(array $links)
    {

        $transaction = $this->client->beginTransaction();

        try {
            foreach ($links as $data) {
                if (false === $this->isAlreadySaved($data['url'])) {
                    $template = "MATCH (u:User)"
                        . " WHERE u.qnoow_id = {userId}"
                        . " CREATE "
                        . " (l:Link {url: {url}, title: {title}, description: {description}, processed: 0})"
                        . ", (l)<-[r:LIKES]-(u) "
                        . " RETURN l;";
                } else {
                    $template = "MATCH (u:User)"
                        . ", (l:Link) "
                        . " WHERE u.qnoow_id = {userId} AND l.url = {url}"
                        . " CREATE UNIQUE (l)<-[r:LIKES]-(u)"
                        . " RETURN l;";
                }

                $query = new Query(
                    $this->client,
                    $template,
                    array(
                        'title'       => $data['title'],
                        'description' => $data['description'],
                        'url'         => $data['url'],
                        'userId'      => (integer)$data['userId']
                    )
                );

                $transaction->addStatements($query);
            }

            $transaction->commit();
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getUnprocessedLinks()
    {

        $template = "MATCH (link:Link) WHERE link.processed = 0 RETURN link LIMIT 20";

        $query = new Query($this->client, $template);

        $resultSet = $query->getResultSet();

        $unprocessedLinks = array();

        foreach ($resultSet as $row) {
            $unprocessedLinks[] = array(
                'url'         => $row['link']->getProperty('url'),
                'description' => $row['link']->getProperty('description'),
                'title'       => $row['link']->getProperty('title'),
                'tempId'      => $row['link']->getProperty('url')
            );
        }

        return $unprocessedLinks;

    }
}
