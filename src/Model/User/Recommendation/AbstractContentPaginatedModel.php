<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User\Recommendation;


use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Query\Row;
use Model\LinkModel;
use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;
use Service\Validator;

abstract class AbstractContentPaginatedModel implements PaginatedInterface
{
    /**
     * @var GraphManager
     */
    protected $gm;

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
     * @param LinkModel $lm
     * @param Validator $validator
     */
    public function __construct(GraphManager $gm, LinkModel $lm, Validator $validator)
    {
        $this->gm = $gm;
        $this->lm = $lm;
        $this->validator = $validator;
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
        $types = isset($filters['type']) ? $filters['type'] : array();
        $count = 0;

        /// Estimation to avoid calculating in real time ///
        $baseSize = 1300000;
        $estimations = array(
            'type' => array(
                'Video' => 0.1,
                'Audio' => 0.02,
                'Image' => 0.008,
                'Creator' => 0.01,
                'Link' => 1,
            ),
            'tag' => 0.001,
        );

        if (empty($types) && !isset($filters['tag'])) {
            return $baseSize;
        }

        if (isset($filters['tag'])) {
            $baseSize = $baseSize * $estimations['tag'];
        }

        if (empty($types)) {
            $types = array('Link');
        }

        foreach ($types as $type) {
            $count += $baseSize * $estimations['type'][$type];
        }

        ///End estimation ///
        
//        $params = array(
//            'userId' => (integer)$id,
//        );
//
//        $qb = $this->gm->createQueryBuilder();
//
//        $qb->matchContentByType($types, 'content')
//            ->where('content.processed = 1');
//
//        if (isset($filters['tag'])) {
//            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
//                ->where('filterTag.name IN { filterTags } ');
//
//            $params['filterTags'] = $filters['tag'];
//        }
//
//        $qb->with('content');
//        $qb->optionalMatch('(user:User {qnoow_id: { userId }})-[l:LIKES|:DISLIKES]->(content)');
//        $qb->returns('count(content)-count(distinct(l)) AS total');
//
//        $qb->setParameters($params);
//
//        $query = $qb->getQuery();
//        $result = $query->getResultSet();
//
//        foreach ($result as $row) {
//            $count = $row['total'];
//        }
//
        return $count;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        return $this->validator->validateRecommendateContent($filters, $this->getChoices());
    }

    /**
     * @param $result ResultSet
     * @param null $id
     * @return array
     */
    public function buildResponseFromResult($result, $id = null)
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

    protected function getChoices()
    {
        return array('type' => $this->lm->getValidTypes());
    }

    /**
     * @param $row Row
     * @param $contentNode Node
     * @param null $id
     * @return array
     */
    protected function completeContent($row, $contentNode, $id = null){
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

        $content['match'] = 0;

        return $content;
    }
}