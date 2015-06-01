<?php

namespace Model\User;

use Paginator\PaginatedInterface;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class ContentComparePaginatedModel implements PaginatedInterface
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
        $hasIds = isset($filters['id']) && isset($filters['id2']);

        if (isset($filters['type'])) {
            $hasValidType = in_array($filters['type'], $this->getValidTypes());
        } else {
            $hasValidType = true;
        }

        $isValid = $hasIds && $hasValidType;

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
        $response = array();
        $id = $filters['id'];
        $id2 = $filters['id2'];

        $showOnlyCommon = false;
        if (isset($filters['showOnlyCommon'])) {
            $showOnlyCommon = $filters['showOnlyCommon'];
        }

        $params = array(
            'UserId' => (integer)$id,
            'UserId2' => (integer)$id2,
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

        $commonQuery = "
                OPTIONAL MATCH
                (u2)-[r2:LIKES|DISLIKES]->(content)
            ";
        if ($showOnlyCommon) {
            $commonQuery = "
                MATCH
                (u2)-[r2:LIKES|DISLIKES]->(content)
            ";
        }

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $query = "
            MATCH
            (u:User), (u2:User)
            WHERE u.qnoow_id = {UserId} AND u2.qnoow_id = {UserId2}
            MATCH
            (u)-[r:LIKES|DISLIKES]->(content:" . $linkType .")
        ";
        $query .= $tagQuery;
        $query .= $commonQuery;
        $query .= "
            OPTIONAL MATCH
            (content)-[:TAGGED]->(tag:Tag)
            OPTIONAL MATCH
            (u2)-[a:AFFINITY]->(content)
            RETURN
            id(content) as id,
            type(r) as rate1,
            type(r2) as rate2,
            content,
            a.affinity as affinity,
            collect(distinct tag.name) as tags,
            labels(content) as types
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
                $content['url'] = $row['content']->getProperty('url');
                $content['title'] = $row['content']->getProperty('title');
                $content['description'] = $row['content']->getProperty('description');
                $content['thumbnail'] = $row['content']->getProperty('thumbnail');
                $content['synonymous'] = $row['content']->getProperty('synonymous');

                foreach ($row['tags'] as $tag) {
                    $content['tags'][] = $tag;
                }

                foreach ($row['types'] as $type) {
                    $content['types'][] = $type;
                }

                $user1 = array();
                $user1['user']['id'] = $id;
                $user1['rate'] = $row['rate1'];
                $content['user_rates'][] = $user1;

                if (null != $row['rate2']) {
                    $user2 = array();
                    $user2['user']['id'] = $id2;
                    $user2['rate'] = $row['rate2'];
                    $content['user_rates'][] = $user2;
                }

                if ($row['content']->getProperty('embed_type')) {
                    $content['embed']['type'] = $row['content']->getProperty('embed_type');
                    $content['embed']['id'] = $row['content']->getProperty('embed_id');
                }

                $content['match'] = $row['affinity'];

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

        $commonQuery = '';
        if (isset($filters['showOnlyCommon'])) {
            $id2 = $filters['id2'];
            if ($filters['showOnlyCommon']) {
                $commonQuery = "
                    MATCH
                    (u2)-[:LIKES|DISLIKES]->(content)
                    WHERE u2.qnoow_id = {UserId2}
                ";
                $params['UserId2'] = (integer)$id2;
            }
        }

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
        $query .= $commonQuery;
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