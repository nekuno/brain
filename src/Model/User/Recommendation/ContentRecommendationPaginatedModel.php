<?php

namespace Model\User\Recommendation;

use Paginator\PaginatedInterface;
use Model\Neo4j\GraphManager;

class ContentRecommendationPaginatedModel implements PaginatedInterface
{
    /**
     * @var array
     */
    private static $validTypes = array('Audio', 'Video', 'Image');

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @param GraphManager $gm
     */
    public function __construct(GraphManager $gm)
    {
        $this->gm = $gm;
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

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: {UserId}})-[affinity:AFFINITY]->(content:' . $linkType .')')
            ->where('NOT (user)-[:LIKES|:DISLIKES]->(content) AND affinity.affinity > 0');

        if (isset($filters['tag'])) {
            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name = { tag }')
            ;

            $params['tag'] = $filters['tag'];
        }

        $qb->optionalMatch('(content)-[:TAGGED]->(tag:Tag)')
            ->returns(
                'affinity',
                'id(content) as id',
                'content',
                'collect(distinct tag.name) as tags',
                'labels(content) as types'
            )
            ->orderBy('affinity.affinity DESC, affinity.updated ASC')
            ->skip('{ offset }')
            ->limit('{ limit }')
        ;

        $qb->setParameters($params);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $content = array();
            $content['id'] = $row['id'];
            $content['url'] = $row['content']->getProperty('url');
            $content['title'] = $row['content']->getProperty('title');
            $content['description'] = $row['content']->getProperty('description');
            foreach ($row['tags'] as $tag) {
                $content['tags'][] = $tag;
            }
            foreach ($row['types'] as $type) {
                $content['types'][] = $type;
            }
            if ($row['content']->getProperty('embed_type')) {
                $content['embed']['type'] = $row['content']->getProperty('embed_type');
                $content['embed']['id'] = $row['content']->getProperty('embed_id');
            }

            $content['match'] = $row['affinity']->getProperty('affinity');

            $response[] = $content;
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

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: {UserId}})-[affinity:AFFINITY]->(content:' . $linkType .')')
            ->where('NOT (user)-[:LIKES|:DISLIKES]->(content) AND affinity.affinity > 0');

        if (isset($filters['tag'])) {
            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name = { tag }')
            ;

            $params['tag'] = $filters['tag'];
        }

        $qb->returns('COUNT(distinct affinity) AS total');

        $qb->setParameters($params);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
    }
} 