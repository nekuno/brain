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
use Service\ImageTransformations;
use Service\Validator;

abstract class AbstractContentPaginatedModel implements PaginatedInterface
{
    const POP_LOWER_LIMIT = 0.0000001;
    const POP_UPPER_LIMIT = 0.01;
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
     * @var ImageTransformations
     */
    protected $it;

    /**
     * @param GraphManager $gm
     * @param LinkModel $lm
     * @param Validator $validator
     * @param ImageTransformations $it
     */
    public function __construct(GraphManager $gm, LinkModel $lm, Validator $validator, ImageTransformations $it)
    {
        $this->gm = $gm;
        $this->lm = $lm;
        $this->validator = $validator;
        $this->it = $it;
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
        $types = isset($filters['type']) ? $filters['type'] : array('Link');
        $typesString = implode(':', $types);

        $qb = $this->gm->createQueryBuilder();
        if (isset($filters['tag'])) {
            $qb->match('(filterTag:Tag)')
                ->where('filterTag.name IN { filterTags }')
                ->setParameter('filterTags', $filters['tag'])
                ->with('filterTag');

            $qb->match('(filterTag)<-[:TAGGED]-(content:' . $typesString . ')');
        } else {
            $qb->match('(content:' . $typesString . ')');
        }
        $qb->returns('COUNT(DISTINCT content) AS total');

        $query = $qb->getQuery();
        $result = $query->getResultSet();
        $total = 0;
        foreach ($result as $row) {
            $total = $row['total'];
        }

        $qb = $this->gm->createQueryBuilder();
        if (isset($filters['tag'])) {
            $qb->match('(filterTag:Tag)')
                ->where('filterTag.name IN { filterTags }')
                ->setParameter('filterTags', $filters['tag'])
                ->with('filterTag');

            $qb->match('(filterTag)<-[:TAGGED]-(content:' . $typesString . ')<-[l:LIKES|:DISLIKES]-(u:User {qnoow_id: { userId }})');
        } else {
            $qb->match('(content:' . $typesString . ')<-[l:LIKES|:DISLIKES]-(u:User {qnoow_id: { userId }})');
        }
        $qb->setParameter('userId', (integer)$id)
            ->returns('count(DISTINCT l) as total');

        $query = $qb->getQuery();
        $result = $query->getResultSet();
        $totalNotIncluded = 0;
        foreach ($result as $row) {
            $totalNotIncluded = $row['total'];
        }

        return $total - $totalNotIncluded > 0 ? $total - $totalNotIncluded : 0;
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
     * @param array $filters
     * @param $limit
     * @param $offset
     * @param string $additionalCondition
     * @return ContentRecommendation[]
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getContentsByPopularity(array $filters, $limit, $offset, $additionalCondition = null)
    {
        $types = isset($filters['type']) ? $filters['type'] : array();

        $response = array('items' => array());
        for ($popLimit = count($filters['type']) > 1 || $filters['type'][0] != 'Link' ? self::POP_UPPER_LIMIT * 10 : self::POP_UPPER_LIMIT; $popLimit >= self::POP_LOWER_LIMIT; $popLimit /= 10) {

            $params = array(
                'offset' => (integer)$offset,
                'limit' => (integer)$limit,
                'popLimit' => $popLimit,
            );

            $qb = $this->gm->createQueryBuilder();

            $qb->setParameters($params);

            if (isset($filters['tag'])) {
                $qb->match('(filterTag:Tag)<-[:TAGGED]-(content:Link)-[:HAS_POPULARITY]-(popularity:Popularity)')
                    ->where('filterTag.name IN { filterTags } ')
                    ->with('content', 'popularity.popularity AS popularity');
                $qb->setParameter('filterTags', $filters['tag']);

            } else {
                $qb->match('(popularity:Popularity)')
                    ->where('popularity.popularity > {popLimit}')
                    ->with('popularity');

                $qb->match('(popularity)-[:HAS_POPULARITY]-(content:Link)')
                    ->with('content', 'popularity.popularity AS popularity');
            }

            $qb->filterContentByType($types, 'content', array('popularity'))
                ->where('content.processed = 1')
                ->with('content', 'popularity');
            if (null !== $additionalCondition) {
                $qb->add('', $additionalCondition);
            }

            $qb->optionalMatch("(content)-[:SYNONYMOUS]->(synonymousLink:Link)")
                ->optionalMatch('(content)-[:TAGGED]->(tag:Tag)')
                ->returns(
                    'id(content) as id',
                    'content',
                    'collect(distinct tag.name) as tags',
                    'labels(content) as types',
                    'COLLECT (DISTINCT synonymousLink) AS synonymous',
                    'popularity'
                )
                ->orderBy('popularity DESC')
                ->skip('{ offset }')
                ->limit('{ limit }');

            $query = $qb->getQuery();
            $result = $query->getResultSet();

            $id = isset($filters['id']) ? $filters['id'] : null;

            $response = $this->buildResponseFromResult($result, $id, $offset);

            if (count($response['items']) >= $limit) {
                break;
            }
        }

        return $response['items'];
    }

    /**
     * @param $result ResultSet
     * @param null $id
     * @param null $offset
     * @return array
     */
    public function buildResponseFromResult(ResultSet $result, $id = null, $offset = null)
    {
        $response = array('items' => array());

        $resultCount = 0;
        /** @var Row $row */
        foreach ($result as $row) {

            $content = new ContentRecommendation();
            /** @var Node $contentNode */
            $contentNode = $row->offsetGet('content');

            $content->setContent($this->lm->buildLink($contentNode));

            $content = $this->completeContent($content, $row, $contentNode, $id);

            if (!$offset && $resultCount <= 4) {
                $thumbnail = $this->getStaticThumbnail($content);
                $content->setStaticThumbnail($thumbnail);
            }
            $resultCount++;

            $response['items'][] = $content;

        }

        return $response;
    }

    protected function getChoices()
    {
        return array('type' => $this->lm->getValidTypes());
    }

    /**
     * @param ContentRecommendation $content
     * @param $row Row
     * @param $contentNode Node
     * @param null $id
     * @return ContentRecommendation
     */
    protected function completeContent(ContentRecommendation $content, $row, $contentNode, $id = null)
    {
        $synonymousArray = array();
        if ($row && $row->offsetGet('synonymous')) {
            foreach ($row->offsetGet('synonymous') as $synonymousLink) {
                /* @var $synonymousLink Node */
                $synonymous = array();
                $synonymous['id'] = $synonymousLink->getId();
                $synonymous['url'] = $synonymousLink->getProperty('url');
                $synonymous['title'] = $synonymousLink->getProperty('title');
                $synonymous['thumbnail'] = $synonymousLink->getProperty('thumbnail');

                $synonymousArray[] = $synonymous;
            }
        }
        $content->setSynonymous($synonymousArray);

        $tags = array();
        if (isset($row['tags'])) {
            foreach ($row['tags'] as $tag) {
                $tags[] = $tag;
            }
        }
        $content->setTags($tags);

        $types = array();
        if (isset($row['types'])) {
            foreach ($row['types'] as $type) {
                $types[] = $type;
            }
        }
        $content->setTypes($types);

        if ($contentNode && $contentNode->getProperty('embed_type')) {
            $content->setEmbed(array(
                'type' => $contentNode->getProperty('embed_type'),
                'id' => $contentNode->getProperty('embed_id'),
            ));
        }

        $content->setMatch(0);

        return $content;
    }

    private function getStaticThumbnail(ContentRecommendation $content)
    {
        if (isset($content->getContent()['thumbnail'])) {
            $thumbnail = $content->getContent()['thumbnail'];
            return $this->it->isGif($thumbnail) ? $this->it->gifToPng($thumbnail) : $thumbnail;
        } elseif (isset($content->getContent()['url']) && $this->it->isImage($content->getContent()['url'])) {
            $thumbnail = $content->getContent()['url'];
            return $this->it->isGif($thumbnail) ? $this->it->gifToPng($thumbnail) : $thumbnail;
        }

        return null;
    }
}