<?php

namespace Model\User\Recommendation;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Query\Row;
use Model\LinkModel;
use Model\User\Affinity\AffinityModel;
use Paginator\PaginatedInterface;
use Model\Neo4j\GraphManager;
use Service\Validator;

class ContentRecommendationPaginatedModel implements PaginatedInterface
{
    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var AffinityModel
     */
    protected $am;

    /**
     * @var LinkModel
     */
    protected $lm;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @param GraphManager $gm
     * @param AffinityModel $am
     * @param LinkModel $lm
     * @param Validator $validator
     */
    public function __construct(GraphManager $gm, AffinityModel $am, LinkModel $lm, Validator $validator)
    {
        $this->gm = $gm;
        $this->am = $am;
        $this->lm = $lm;
        $this->validator = $validator;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $userId = isset($filters['id'])? $filters['id'] : null;
        $this->validator->validateUserId($userId);

        return $this->validator->validateRecommendateContent($filters, $this->getChoices());
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

        if ((integer)$limit == 0) {
            return array();
        }
        $return = array('items' => array());

        $id = $filters['id'];

        $params = array(
            'userId' => (integer)$id,
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        $linkLabels = $this->lm->buildOptionalTypesLabel($filters);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: { userId }})-[affinity:AFFINITY]->(content:' . $linkLabels . ')')
            ->where('NOT (user)-[:LIKES|:DISLIKES]->(content) AND affinity.affinity > 0 AND content.processed = 1');

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

        $response = $this->buildResponseFromResult($result, $id);
        $return['items'] = array_merge($return['items'], $response['items']);

        $needContent = $this->needMoreContent($limit, $return);
        if ($needContent) {
            $newItems = $this->lm->getLivePredictedContent($id, $needContent, 2, $filters);
            foreach ($newItems as &$newItem) {
                $newItem = array_merge($newItem, $this->completeContent(null, null, $id, $newItem['content']['id']));
            }
            $return['items'] = array_merge($return['items'], $newItems);
        }

        $needContent = $this->needMoreContent($limit, $return);
        if ($needContent) {

            $foreign = 0;
            if (isset($filters['foreign'])) {
                $foreign = $filters['foreign'];
            }

            $foreignResult = $this->getForeignContent($filters, $needContent, $foreign);
            $return['items'] = array_merge($return['items'], $foreignResult['items']);
            $return['newForeign'] = $foreignResult['foreign'];
        }

        //Works with ContentPaginator (accepts $result), not Paginator (accepts $result['items'])
        return $return;
    }

    /**
     * @param $filters
     * @param $limit
     * @param $foreign
     * @return array (items, foreign = # of links database searched, -1 if total)
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getForeignContent($filters, $limit, $foreign)
    {

        $id = $filters['id'];

        if ((integer)$limit == 0) {
            return array();
        }

        $linkLabels = $this->lm->buildOptionalTypesLabel($filters);

        $pageSizeMultiplier = 1; //small may make queries slow, big may skip results
        if (isset($filters['tag'])) {
            $pageSizeMultiplier *= 5;
        }

        $internalLimit = $limit * $pageSizeMultiplier;

        $maxPagesSearched = 10000; //bigger may get more contents but it's slower near the limit

        $databaseSize = $this->lm->countAllLinks($filters);

        $pagesSearched = min(array($databaseSize / $internalLimit, $maxPagesSearched));

        $internalPaginationLimit = $foreign + $pagesSearched * $internalLimit;

        $params = array(
            'userId' => (integer)$id,
            'limit' => (integer)$limit,
            'internalOffset' => (integer)$foreign,
            'internalLimit' => $internalLimit,
        );

        $items = array();

        while (count($items) < $limit && $params['internalOffset'] < $internalPaginationLimit) {

            $qb = $this->gm->createQueryBuilder();
            $qb->match('(user:User {qnoow_id: { userId }})');
            if (isset($filters['tag'])){
                $qb->match('(content:' . $linkLabels . ')-[:TAGGED]->(filterTag:Tag)')
                    ->where('filterTag.name = { tag }', 'content.processed = 1');

                $params['tag'] = $filters['tag'];
            } else {
                $qb->match('(content:' . $linkLabels . '{processed: 1})');
            }

            $qb->with('user', 'content')
                ->orderBy('content.created DESC')
                ->skip('{internalOffset}')
                ->limit('{internalLimit}');

            $qb->with('user', 'content')
                ->where('NOT (user)-[:AFFINITY]-(content)',
                    'NOT (user)-[:LIKES]-(content)',
                    'NOT (user)-[:DISLIKES]-(content)');

            $qb->with('content')
                ->limit('{ limit }');

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

            $qb->setParameters($params);
            $query = $qb->getQuery();
            $result = $query->getResultSet();

            $response = $this->buildResponseFromResult($result, $id);

            $items = array_merge($items, $response['items']);

            $params['internalOffset'] += $internalLimit;
        }

        $return = array('items' => array_slice($items, 0, $limit));

        if ($params['internalOffset'] >= $databaseSize) {
            $params['internalOffset'] = -1;
        }
        $return['foreign'] = $params['internalOffset'];

        return $return;
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

        $linkLabels = $this->lm->buildOptionalTypesLabel($filters);

        $qb = $this->gm->createQueryBuilder();

        if (isset($filters['tag'])) {
            $qb->match('(content:' . $linkLabels . '{processed: 1})-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name = { tag }');

            $params['tag'] = $filters['tag'];
        } else {
            $qb->match('(content:' . $linkLabels . '{processed: 1})');
        }

        $qb->with('content');
        $qb->optionalMatch('(user:User {qnoow_id: { userId }})-[l:LIKES|:DISLIKES]->(content)');
        $qb->returns('count(content)-count(distinct(l)) AS total');

        $qb->setParameters($params);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
    }

    /**
     * @param $result ResultSet
     * @param $id
     * @return array
     */
    public function buildResponseFromResult($result, $id)
    {
        $response = array('items' => array());

        /** @var Row $row */
        foreach ($result as $row) {

            $content = array();
            /** @var Node $contentNode */
            $contentNode = $row->offsetGet('content');

            $content['content'] = $this->lm->buildLink($contentNode);

            $content = array_merge($content, $this->completeContent($row, $contentNode, $id));

            $response['items'][] = $content;

        }

        return $response;
    }

    /**
     * @param $limit int
     * @param $response array
     * @return int
     */
    protected function needMoreContent($limit, $response)
    {
        $moreContent = $limit - count($response['items']);
        if ($moreContent <= 0) {
            return 0;
        }

        return $moreContent;
    }

    /**
     * @param $row Row
     * @param $contentNode Node
     * @param $id
     * @param null $contentId
     * @return array
     */
    protected function completeContent($row = null, $contentNode = null, $id = null, $contentId = null)
    {
        $content = array();

        $content['synonymous'] = array();

        if ($row && $row->offsetGet('synonymous')) {
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

        $content['tags'] = array();
        if (isset($row['tags'])) {
            foreach ($row['tags'] as $tag) {
                $content['tags'][] = $tag;
            }
        }

        $content['types'] = array();
        if (isset($row['types'])) {
            foreach ($row['types'] as $type) {
                $content['types'][] = $type;
            }
        }

        if ($contentNode && $contentNode->getProperty('embed_type')) {
            $content['embed']['type'] = $contentNode->getProperty('embed_type');
            $content['embed']['id'] = $contentNode->getProperty('embed_id');
        }

        $affinity = array('affinity' => 0);

        if (!$contentId) {
            if ($contentNode) {
                $contentId = $contentNode->getId();
            }
            if (isset($row['id'])) {
                $contentId = $row['id'];
            }
        }

        if ($contentId && $id) {
            $affinity = $this->am->getAffinity((integer)$id, $contentId);
        }

        $content['match'] = $affinity['affinity'];

        return $content;
    }

    protected function getChoices()
    {
        return array('type' => $this->lm->getValidTypes());
    }
} 