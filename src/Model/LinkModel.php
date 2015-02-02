<?php

namespace Model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;

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
     * @var GraphManager
     */
    protected $gm;

    public function __construct(Client $client, GraphManager $gm)
    {

        $this->client = $client;
        $this->gm = $gm;
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
                $additionalLabels .= ":" . $label;
            }
        }

        $additionalFields = "";
        if (isset($data['additionalFields'])) {
            foreach ($data['additionalFields'] as $field => $value) {
                $additionalFields .= ", l." . $field . " = '" . $value . "'";
            }
        }

        $language = "";
        if (isset($data['language'])) {
            $language = $data['language'];
        }

        $qb = $this->gm->createQueryBuilder();

        if (false === $this->isAlreadySaved($data['url'])) {

            $qb->match('(u:User)')
                ->where('u.qnoow_id = { userId }')
                ->create('(l:Link' . $additionalLabels . ')')
                ->set(
                    'l.url = { url }',
                    'l.title = { title }',
                    'l.description = { description }',
                    'l.language = { language }',
                    'l.processed = 1',
                    'l.created =  timestamp()' . $additionalFields
                )
                ->create('(u)-[r:LIKES]->(l)')
                ->returns('l');

        } else {

            $qb->match('(u:User)', '(l:Link)')
                ->where('u.qnoow_id = { userId }', 'l.url = { url }')
                ->createUnique('(u)-[r:LIKES]->(l)')
                ->returns('l');

        }

        $qb->setParameters(
            array(
                'title' => $data['title'],
                'description' => $data['description'],
                'url' => $data['url'],
                'userId' => (integer)$data['userId'],
                'language' => $language
            )
        );

        $query = $qb->getQuery();

        return $query->getResultSet();
    }

    public function updateLink(array $data, $processed = false)
    {
        $additionalLabels = "";
        if (isset($data['additionalLabels'])) {
            foreach ($data['additionalLabels'] as $label) {
                $additionalLabels .= ", link:" . $label;
            }
        }

        $additionalFields = "";
        if (isset($data['additionalFields'])) {
            foreach ($data['additionalFields'] as $field => $value) {
                $additionalFields .= ", link." . $field . " = '" . $value . "'";
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
                'tempId' => $data['tempId'],
                'url' => $data['url'],
                'title' => $data['title'],
                'description' => $data['description'],
                'language' => $language
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

        $qb = $this->gm->createQueryBuilder();
        $qb->merge('(tag:Tag {name: { name }})')
            ->setParameter('name', $tag['name'])
            ->returns('tag');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();
        /* @var $node Node */
        $node = $row->offsetGet('tag');

        if (isset($tag['additionalLabels']) && is_array($tag['additionalLabels'])) {
            $node->addLabels($this->gm->makeLabels($tag['additionalLabels']));
        }

        if (isset($tag['additionalFields']) && is_array($tag['additionalFields'])) {
            foreach ($tag['additionalFields'] as $field => $value) {
                $node->setProperty($field, $value);
            }
            $node->save();
        }

        return $node;

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
        $query = new Query($this->client, $template, $params);

        return $query->getResultSet();

    }

    public function getUnprocessedLinks($limit = 100)
    {

        $template = "MATCH (link:Link) WHERE link.processed = 0 RETURN link LIMIT {limit}";

        $query = new Query($this->client, $template, array('limit' => (integer)$limit));

        $resultSet = $query->getResultSet();

        $unprocessedLinks = array();

        foreach ($resultSet as $row) {
            $unprocessedLinks[] = array(
                'url' => $row['link']->getProperty('url'),
                'description' => $row['link']->getProperty('description'),
                'title' => $row['link']->getProperty('title'),
                'tempId' => $row['link']->getProperty('url'),
            );
        }

        return $unprocessedLinks;

    }

    public function updatePopularity(array $filters)
    {
        $parameters = array();

        $template = "
            MATCH (:Link)-[r:LIKES]-(:User)
                WITH count(DISTINCT r) AS total
                WHERE total > 1
                WITH  total AS max
                ORDER BY max DESC
                LIMIT 1
        ";

        if (isset($filters['userId'])) {
            $template .= "
                MATCH (:User {qnoow_id: {id}})-[LIKES]-(l:Link)
            ";
            $parameters['id'] = (integer)$filters['userId'];
        } else {
            $template .= "
                MATCH (l:Link)
            ";
        }

        $template .= "
            MATCH (l)-[r:LIKES]-(:User)
                WITH l, count(DISTINCT r) AS total, max
                WHERE total > 1
		        WITH l, toFloat(total) AS total, toFloat(max) AS max
        ";

        if (isset($filters['limit'])) {
            $template .= "
                ORDER BY HAS(l.popularity_timestamp), l.popularity_timestamp
		        LIMIT {limit}
            ";
            $parameters['limit'] = (integer)$filters['limit'];
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
}
