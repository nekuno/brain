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
            'UserId' => (integer)$id,
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: {UserId}})-[affinity:AFFINITY]->(content:' . $linkType . ')')
            ->where('NOT (user)-[:LIKES|:DISLIKES]->(content) AND affinity.affinity > 0');

        if (isset($filters['tag'])) {
            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name = { tag }');

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
            ->limit('{ limit }');

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

        //If there is not enough content, we pick recent suitable content and add it to response

        //Obtain maximum number of available links
        $max = 0;
        if ((count($response) < (integer)$limit)) {
            $qb = $this->gm->createQueryBuilder();
            $params = array(
                'UserId' => (integer)$id
            );
            $qb->match('(user:User {qnoow_id: {UserId}})');
            if (isset($filters['tag'])) {
                $qb->match('(content:' . $linkType . ')-[:TAGGED]->(filterTag:Tag)');

                $params['tag'] = $filters['tag'];
            } else {
                $qb->match('(content:' . $linkType . ')');

            }
            $qb->with('user,count(distinct(content)) as contents');
            $qb->optionalMatch('(user)-[:AFFINITY|:LIKES|:DISLIKES]->(used:' . $linkType . ')');
            $qb->setParameters($params);
            $qb->returns('contents-count(distinct(used)) AS max');
            $query = $qb->getQuery();
            $result = $query->getResultSet();

            $max = $result[0]->offsetGet('max');
        }

        //We get desired links using an internal pagination
        $internalLoops = 0;
        $internalLimit = 100;
        while (((count($response) < (integer)$limit)
            && ((integer)$offset + ($internalLimit * $internalLoops) < $max))
        ) {

            $qb = $this->gm->createQueryBuilder();

            $params = array(
                'UserId' => (integer)$id,
                'offset' => (integer)$offset + ($internalLimit * $internalLoops),
                'limit' => (integer)$limit - count($response),
                'internalLimit' => $internalLimit,
            );

            $qb->match('(user:User {qnoow_id: {UserId}})');

            if (isset($filters['tag'])) {
                $qb->match('(content:' . $linkType . ')-[:TAGGED]->(filterTag:Tag)')
                    ->with('user,content')
                    ->limit('{internalLimit}')
                    ->where('filterTag.name = { tag }',
                        'NOT (user)-[:AFFINITY|:LIKES|:DISLIKES]->(content)');

                $params['tag'] = $filters['tag'];
            } else {
                $qb->match('(content:' . $linkType . ')')
                    ->with('user,content')
                    ->limit('{internalLimit}')
                    ->where('NOT (user)-[:AFFINITY|:LIKES|:DISLIKES]->(content)');
            }

            $qb->with('content')
                ->orderBy('content.timestamp DESC')
                ->skip('{offset}')
                ->limit('{limit}');
            $qb->setParameters($params);
            $qb->optionalMatch('(content)-[:TAGGED]->(tag:Tag)')
                ->returns(
                    'id(content) as id',
                    'content',
                    'collect(distinct tag.name) as tags',
                    'labels(content) as types')
                ->orderBy('content.timestamp DESC');
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

                $content['match'] = "?";

                    $response[] = $content;

            }
            $internalLoops++;
        }
        //TODO Eliminar esto, debug
//        if (isset($max)){
//            $response['max']=$max;
//        }
//
//        $response['internalLoops']=$internalLoops;
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

        if (isset($filters['tag'])) {
            $qb->match('(content:' . $linkType . ')-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name = { tag }');

            $params['tag'] = $filters['tag'];
        } else {
            $qb->match('(content:' . $linkType . ')');
        }

        $qb->with('count(content) AS max');
        $qb->optionalMatch('(user:User {qnoow_id: {UserId}})-[:LIKES|:DISLIKES]->(l:' . $linkType . ')');
        $qb->returns('max-count(distinct(l)) AS total');

        $qb->setParameters($params);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
    }
} 