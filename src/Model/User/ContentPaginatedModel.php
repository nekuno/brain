<?php

namespace Model\User;

use Paginator\PaginatedInterface;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class ContentPaginatedModel implements PaginatedInterface
{
    /**
     * @var array
     */
    private static $validTypes = array('Audio', 'Video', 'Image');

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

    public function getValidTypes()
    {
        return Self::$validTypes;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $hasId = isset($filters['id']);

        if (isset($filters['type'])) {
            $hasValidType = in_array($filters['type'], $this->getValidTypes());
        } else {
            $hasValidType = true;
        }

        $isValid = $hasId && $hasValidType;

        return $isValid;
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
        $id = $filters['id'];
        $response = array();

        $params = array(
            'UserId' => (integer)$id,
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        $tagQuery = '';
        if (isset($filters['tag'])) {
            $tagQuery = "
                MATCH
                (content)-[:TAGGED]->(filterTag:Tag)
                WHERE filterTag.name = {tag}
            ";
            $params['tag'] = $filters['tag'];
        }

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $query = "
            MATCH
            (u:User)
            WHERE u.qnoow_id = {UserId}
            MATCH
            (u)-[r:LIKES|DISLIKES]->(content:" . $linkType .")
        ";
        $query .= $tagQuery;
        $query .= "
            OPTIONAL MATCH
            (content)-[:TAGGED]->(tag:Tag)
            RETURN
            id(content) as id,
            type(r) as rate,
            content,
            collect(distinct tag.name) as tags,
            labels(content) as types
            ORDER BY content.created DESC
            SKIP {offset}
            LIMIT {limit}
            ;
         ";

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

                $content['id'] = $row['id'];
                $content['type'] = $row['type'];
                $content['url'] = $row['content']->getProperty('url');
                $content['title'] = $row['content']->getProperty('title');
                $content['description'] = $row['content']->getProperty('description');
                $content['thumbnail'] = $row['content']->getProperty('thumbnail');

                $params = array('linkId' => $content['id']);
                $query = "
                    MATCH
                    (l:Link)-[synonymous:SYNONYMOUS]->(synonymousLink:Link)
                    WHERE l.id = { linkId }
                    RETURN synonymousLink
                ";
                //Create the Neo4j query object
                $contentQuery = new Query(
                    $this->client,
                    $query,
                    $params
                );

                $content['synonymous'] = array();

                //Execute query
                try {
                    $synonymousResult = $contentQuery->getResultSet();
                    foreach ($synonymousResult as $synonymousRow) {
                        $content['synonymous']['url'] = $synonymousRow['content']->getProperty('url');
                    }
                } catch (\Exception $e) {
                    throw $e;
                }

                foreach ($row['tags'] as $tag) {
                    $content['tags'][] = $tag;
                }

                foreach ($row['types'] as $type) {
                    $content['types'][] = $type;
                }

                $user = array();
                $user['user']['id'] = $id;
                $user['rate'] = $row['rate'];
                $content['user_rates'][] = $user;

                if ($row['content']->getProperty('embed_type')) {
                    $content['embed']['type'] = $row['content']->getProperty('embed_type');
                    $content['embed']['id'] = $row['content']->getProperty('embed_id');
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
        $id = $filters['id'];
        $count = 0;

        $params = array(
            'UserId' => (integer)$id,
        );

        $tagQuery = '';
        if (isset($filters['tag'])) {
            $tagQuery = "
                MATCH
                (content)-[:TAGGED]->(filterTag:Tag)
                WHERE filterTag.name = {tag}
            ";
            $params['tag'] = $filters['tag'];
        }

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $query = "
            MATCH
            (u:User)
            WHERE u.qnoow_id = {UserId}
            MATCH
            (u)-[r:LIKES|DISLIKES]->(content:" . $linkType . ")
        ";
        $query .= $tagQuery;
        $query .= "
            RETURN
            count(r) as total
            ;
         ";

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
                $count = $row['total'];
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $count;
    }
}