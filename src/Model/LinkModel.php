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
        $additionalLabels = "";
        if (isset($data['additionalLabels'])) {
            foreach ($data['additionalLabels'] as $label) {
                $additionalLabels .= ":".$label;
            }
        }

        $additionalFields = "";
        if (isset($data['additionalFields'])) {
            foreach ($data['additionalFields'] as $field => $value) {
                $additionalFields .= ", l.".$field." = '".$value."'";
            }
        }

        $language = "";
        if (isset($data['language'])) {
            $language = $data['language'];
        }

        if (false === $this->isAlreadySaved($data['url'])) {
            $template = "MATCH (u:User)"
                . " WHERE u.qnoow_id = {userId}"
                . " CREATE "
                . " (l:Link".$additionalLabels.") "
                ." SET l.url = {url}, l.title = {title}, l.description = {description}, "
                . " l.language = {language}, l.processed = 1, l.created =  timestamp() "
                . $additionalFields
                . " CREATE (u)-[r:LIKES]->(l) "
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
                'userId'      => (integer)$data['userId'],
                'language'    => $language
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
        $additionalLabels = "";
        if (isset($data['additionalLabels'])) {
            foreach ($data['additionalLabels'] as $label) {
                $additionalLabels .= ", link:".$label;
            }
        }

        $additionalFields = "";
        if (isset($data['additionalFields'])) {
            foreach ($data['additionalFields'] as $field => $value) {
                $additionalFields .= ", link.".$field." = '".$value."'";
            }
        }

        $language = "";
        if (isset($data['language'])) {
            $language = $data['language'];
        }

        $template = "MATCH (link:Link)"
            . " WHERE link.url = { tempId } "
            . " SET link.url = { url }"
            . " , link.title = { title }"
            . " , link.description = { description }"
            . " , link.language = { language }"
            . " , link.processed = " . (integer)$processed
            . " , link.updated = timestamp() "
            . $additionalLabels . $additionalFields
            . " RETURN link;";

        $query = new Query(
            $this->client,
            $template,
            array(
                'tempId'      => $data['tempId'],
                'url'         => $data['url'],
                'title'       => $data['title'],
                'description' => $data['description'],
                'language'    => $language
            )
        );

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

        $additionalLabels = "";
        if (isset($tag['additionalLabels'])) {
            foreach ($tag['additionalLabels'] as $label) {
                $additionalLabels .= ":".$label;
            }
        }

        $additionalFields = "";
        if (isset($tag['additionalFields'])) {
            foreach ($tag['additionalFields'] as $field => $value) {
                $additionalFields .= ", tag.".$field." = '".$value."'";
            }
        }

        $params = array(
            'name' => $tag['name'],
        );

        $template = "CREATE (tag:Tag".$additionalLabels.")"
            . "SET tag.name = { name }".$additionalFields
            . "RETURN tag";

        $query = new Query($this->client, $template, $params);

        return $query->getResultSet();

    }

    public function addTag($link, $tag)
    {

        $template = "MATCH (link:Link)"
            . ", (tag:Tag)"
            . " WHERE link.url = { url } AND tag.name = { tag }"
            . " CREATE UNIQUE (link)-[:TAGGED]->(tag)";

        $params = array(
            'url' => $link['url'],
            'tag' => $tag['name'],
        );
        $query  = new Query($this->client, $template, $params);

        return $query->getResultSet();

    }

    public function getUnprocessedLinks($limit = 100)
    {

        $template = "MATCH (link:Link) WHERE link.processed = 0 RETURN link LIMIT {limit}";

        $query = new Query($this->client, $template, array('limit' => (integer) $limit));

        $resultSet = $query->getResultSet();

        $unprocessedLinks = array();

        foreach ($resultSet as $row) {
            $unprocessedLinks[] = array(
                'url'         => $row['link']->getProperty('url'),
                'description' => $row['link']->getProperty('description'),
                'title'       => $row['link']->getProperty('title'),
                'tempId'      => $row['link']->getProperty('url'),
            );
        }

        return $unprocessedLinks;

    }

    public function updatePopularity($limit = null)
    {
        $parameters = array();

        $template = "
            MATCH (:Link)-[r:LIKES]-(:User)
                WITH count(DISTINCT r) AS total
                WHERE total > 1
                WITH  total AS max
                ORDER BY max DESC
                LIMIT 1
            MATCH (l:Link)-[r:LIKES]-(:User)
                WITH l, count(DISTINCT r) AS total, max
                WHERE total > 1
		        WITH l, toFloat(total) AS total, toFloat(max) AS max
        ";

        if (null !== $limit) {
            $template .= "
                ORDER BY HAS(l.popularity_timestamp), l.popularity_timestamp
		        LIMIT {limit}
            ";
            $parameters['limit'] = (integer) $limit;
        }

        $template .= "
                SET
                    l.popularity = (total/max)^3,
                    l.unpopularity = (1-(total/max))^3,
                    l.popularity_timestamp = timestamp()
        ";

        $query = new Query($this->client, $template, $parameters);

        $query->getResultSet();

        return true;
    }
}
