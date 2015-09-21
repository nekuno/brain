<?php

namespace Model\User\Recommendation;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\User\Affinity\AffinityModel;
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
     * @var AffinityModel
     */
    protected $am;

    /**
     * @param GraphManager $gm
     * @param AffinityModel $am
     */
    public function __construct(GraphManager $gm, AffinityModel $am)
    {
        $this->gm = $gm;
        $this->am = $am;
    }

    public function getValidTypes()
    {
        return self::$validTypes;
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

        if ((integer)$limit == 0) {
            return array();
        }
        $response = array();

        $params = array(
            'userId' => (integer)$id,
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: { userId }})-[affinity:AFFINITY]->(content:' . $linkType . ')')
            ->where('NOT (user)-[:LIKES|:DISLIKES]->(content) AND affinity.affinity > 0');

        if (isset($filters['tag'])) {
            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name = { tag }');

            $params['tag'] = $filters['tag'];
        }

        $qb->optionalMatch("(content)-[:SYNONYMOUS]->(synonymousLink:Link)")
            ->optionalMatch('(content)-[:TAGGED]->(tag:Tag)')
            ->returns(
                'affinity',
                'id(content) as id',
                'content',
                'collect(distinct tag.name) as tags',
                'labels(content) as types',
                'COLLECT (DISTINCT synonymousLink) AS synonymous'
            )
            ->orderBy('affinity.affinity DESC, affinity.updated ASC')
            ->skip('{ offset }')
            ->limit('{ limit }');

        $qb->setParameters($params);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $response = array_merge($response, $this->buildResponseFromResult($result, $id));

        return $response;
    }

    /**
     * @param $filters
     * @param $limit
     * @return array
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getForeignContent($filters, $limit)
    {

        $id = $filters['id'];

        if ((integer)$limit == 0) {
            return array();
        }

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $foreign = 0;
        if (isset($filters['foreign'])) {
            $foreign = $filters['foreign'];
        }

        $internalLimit = $limit;
        if (isset($filters['type']) || isset($filters['tag'])) {
            $internalLimit = 10 * $limit;
        }

        $params = array(
            'userId' => (integer)$id,
            'limit' => (integer)$limit,
            'offset' => (integer)$foreign,

            'internalOffset' => 0,
            'internalLimit' => $internalLimit,
        );

        $response = array();
        while (count($response) < $limit) {

            $qb = $this->gm->createQueryBuilder();
            $qb->match('(user:User {qnoow_id: { userId }})');
            $qb->match('(content:' . $linkType . ')');
            $qb->with('user', 'content')
                ->orderBy('content.timestamp DESC')
                ->skip('{internalOffset}')
                ->limit('{internalLimit}');

            if (isset($filters['tag'])) {
                $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                    ->where('filterTag.name = { tag }', 'NOT (user)-[:AFFINITY|:LIKES|:DISLIKES]->(content)');

                $params['tag'] = $filters['tag'];
            } else {
                $qb->match('content');
                $qb->where('NOT (user)-[:AFFINITY|:LIKES|:DISLIKES]->(content)');
            }

            $qb->with('content')
                ->skip('{offset}')
                ->limit('{ limit }');
            $qb->setParameters($params);
            $qb->optionalMatch('(content)-[:TAGGED]->(tag:Tag)')
                ->optionalMatch('(content)-[:SYNONYMOUS]->(synonymousLink:Link)')
                ->returns(
                    'id(content) as id',
                    'content',
                    'collect(distinct tag.name) as tags',
                    'labels(content) as types',
                    'COLLECT (DISTINCT synonymousLink) AS synonymous'
                )
                ->orderBy('content.timestamp DESC');
            $query = $qb->getQuery();
            $result = $query->getResultSet();

            $response = array_merge($this->buildResponseFromResult($result, $id));

            $params['internalOffset'] += 100;
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
            'userId' => (integer)$id,
        );

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $qb = $this->gm->createQueryBuilder();

        if (isset($filters['tag'])) {
            $qb->match('(content:' . $linkType . ')-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name = { tag }');

            $params['tag'] = $filters['tag'];
        } else {
            $qb->match('(content:' . $linkType . ')');
        }

        $qb->with('count(content) AS max');
        $qb->optionalMatch('(user:User {qnoow_id: { userId }})-[:LIKES|:DISLIKES]->(l:' . $linkType . ')');
        $qb->returns('max-count(distinct(l)) AS total');

        $qb->setParameters($params);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
    }

    public function buildResponseFromResult($result, $id)
    {
        $response = array();

        /** @var Row $row */
        foreach ($result as $row) {
            $content = array();
            $content['id'] = $row->offsetGet('id');

            /** @var Node $contentNode */
            $contentNode = $row->offsetGet('content');
            $content['url'] = $contentNode->getProperty('url');
            $content['title'] = $contentNode->getProperty('title');
            $content['description'] = $contentNode->getProperty('description');
            $content['thumbnail'] = $contentNode->getProperty('thumbnail');
            $content['synonymous'] = array();
            if ($row->offsetGet('synonymous')) {
                foreach ($row->offsetGet('synonymous') as $synonymousLink) {
                    /* @var $synonymousLink Node */
                    $synonymous = array();
                    $synonymous['id'] = $synonymousLink->getId();
                    $synonymous['url'] = $synonymousLink->getProperty('url');
                    $synonymous['title'] = $synonymousLink->getProperty('title');
                    $synonymous['thumbnail'] = $synonymousLink->getProperty('thumbnail');

                    $content['synonymous'][] = $synonymous;
                }
            }
            foreach ($row['tags'] as $tag) {
                $content['tags'][] = $tag;
            }
            foreach ($row['types'] as $type) {
                $content['types'][] = $type;
            }
            if ($contentNode->getProperty('embed_type')) {
                $content['embed']['type'] = $contentNode->getProperty('embed_type');
                $content['embed']['id'] = $contentNode->getProperty('embed_id');
            }

            $affinity = $this->am->getAffinity((integer)$id, $row['id']);
            $content['match'] = $affinity['affinity'];

            $response[] = $content;

        }

        return $response;
    }
} 