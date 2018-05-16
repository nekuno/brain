<?php

namespace Model\Recommendation;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Affinity\Affinity;
use Model\Link\LinkManager;
use Model\Affinity\AffinityManager;
use Model\Neo4j\GraphManager;
use Service\ImageTransformations;
use Service\Validator\FilterContentValidator;

class ContentRecommendationPaginatedManager extends AbstractContentPaginatedManager
{

    /**
     * @var AffinityManager
     */
    protected $am;

    /**
     * @param GraphManager $gm
     * @param AffinityManager $am
     * @param LinkManager $lm
     * @param FilterContentValidator $validator
     * @param ImageTransformations $it
     */
    public function __construct(GraphManager $gm, AffinityManager $am, LinkManager $lm, FilterContentValidator $validator, ImageTransformations $it)
    {
        parent::__construct($gm, $lm, $validator, $it);
        $this->am = $am;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $filters['userId'] = isset($filters['id'])? $filters['id'] : null;
        return parent::validateFilters($filters);
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
        $filters['type'] = isset($filters['type']) ? $filters['type'] : array('Link');

        $params = array(
            'userId' => (integer)$id,
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );
        $typesString = implode(':', $filters['type']);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: { userId }})-[affinity:AFFINITY]->(content:' . $typesString . ')')
            ->where('content.processed = 1 AND NOT (user)-[:LIKES|:DISLIKES|:IGNORES]->(content)')
            ->with('affinity, content');

        if (isset($filters['tag'])) {
            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name IN { filterTags } ');

            $params['filterTags'] = $filters['tag'];
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

        $response = $this->buildResponseFromResult($result, $id, $offset);
        $return['items'] = array_merge($return['items'], $response['items']);

        $needContent = $this->needMoreContent($limit, $return);
        if ($needContent) {
            $newItems = $this->lm->getLivePredictedContent($id, $needContent, 2, $filters);
            foreach ($newItems as &$newItem) {
                $contentId = $newItem->getContent()->getId();
                $newItem = $this->completeContent($newItem, null, null, $id, $contentId);
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

        $needContent = $this->needMoreContent($limit, $return);
        if ($needContent) {
            $ignored = 0;
            if (isset($filters['ignored'])) {
                $ignored = $filters['ignored'];
            }

            $ignoredResult = $this->getIgnoredContent($filters, $needContent, $ignored);
            $return['items'] = array_merge($return['items'], $ignoredResult['items']);
            $return['newIgnored'] = $ignoredResult['ignored'];
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
        $condition = "MATCH (u:User{qnoow_id:$id}) WHERE NOT(u)-[:LIKES|:DISLIKES|:IGNORES|:AFFINITY]->(content)";

        $items = $this->getContentsByPopularity($filters, $limit, $foreign, $condition);
        
        $return = array('items' => array_slice($items, 0, $limit) );
        $return['foreign'] = $foreign + count($return['items']);

        return $return;
    }

    /**
     * @param $filters
     * @param $limit
     * @param $ignored
     * @return array (items, ignored = # of links database searched, -1 if total)
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getIgnoredContent($filters, $limit, $ignored)
    {
        $id = $filters['id'];
        $condition = "MATCH (u:User{qnoow_id:$id})-[:IGNORES]->(content)";

        $items = $this->getContentsByPopularity($filters, $limit, $ignored, $condition);

        $return = array('items' => array_slice($items, 0, $limit) );
        $return['ignored'] = $ignored + count($return['items']);

        return $return;
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
     * @param ContentRecommendation $contentRecommendation
     * @param $row Row
     * @param $contentNode Node
     * @param $id
     * @param null $contentId
     * @return ContentRecommendation
     */
    public function completeContent(ContentRecommendation $contentRecommendation, $row = null, $contentNode = null, $id = null, $contentId = null)
    {
        $contentRecommendation = parent::completeContent($contentRecommendation, $row, $contentNode);

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
        } else {
            $affinity = new Affinity();
        }

        $contentRecommendation->setMatch($affinity->getAffinity());

        return $contentRecommendation;
    }
} 