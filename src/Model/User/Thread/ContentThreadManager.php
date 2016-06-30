<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User\Thread;


use Model\LinkModel;
use Model\Neo4j\GraphManager;
use Model\User\Filters\FilterContentManager;
use Model\User\Recommendation\ContentRecommendation;
use Model\User\Recommendation\ContentRecommendationPaginatedModel;

class ContentThreadManager
{

    /**
     * @var $graphManager GraphManager
     */
    protected $graphManager;

    /**
     * @var $linkModel LinkModel
     */
    protected $linkModel;

    /**
     * @var FilterContentManager
     */
    protected $filterContentManager;

    protected $crpm;

    public function __construct(GraphManager $graphManager, LinkModel $linkModel, FilterContentManager $filterContentManager, ContentRecommendationPaginatedModel $crpm)
    {
        $this->graphManager = $graphManager;
        $this->linkModel = $linkModel;
        $this->filterContentManager = $filterContentManager;
        $this->crpm = $crpm;
    }

    public function update($id, array $filters)
    {
        return $this->filterContentManager->updateFilterContentByThreadId($id, $filters);
    }

    /**
     * @param $id
     * @param $name
     * @param $type
     * @return ContentThread
     */
    public function buildContentThread($id, $name, $type)
    {
        $thread = new ContentThread($id, $name, $type);

        $filters = $this->filterContentManager->getFilterContentByThreadId($thread->getId());
        $thread->setFilterContent($filters);

        return $thread;
    }

    public function getCached(Thread $thread)
    {
        $parameters = array(
            'id' => $thread->getId(),
        );

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->optionalMatch('(thread)<-[:HAS_THREAD]-(u:User)')
            ->with('thread', 'u.qnoow_id as u')
            ->optionalMatch('(thread)-[:RECOMMENDS]->(r:Link)')
            ->with('u', 'r')
            ->optionalMatch("(r)-[:SYNONYMOUS]->(synonymousLink:Link)")
            ->with('u', 'r', 'collect(distinct(synonymousLink)) AS synonymous')
            ->optionalMatch('(r)-[:TAGGED]->(tag:Tag)')
            ->with('u', 'r', 'synonymous', 'collect(distinct(tag.name)) AS tags')

            ->returns('u, r, synonymous, tags');
        $qb->setParameters($parameters);
        $result = $qb->getQuery()->getResultSet();

        $cached = array();
        foreach ($result as $row){
            $linkNode = $row->offsetGet('r');
            if (null == $linkNode) {
                continue;
            }
            $link =  $this->linkModel->buildLink($linkNode);
            $content = new ContentRecommendation();
            $content->setContent($link);

            $content = $this->crpm->completeContent($content, $row, $linkNode, $row->offsetGet('u'));

            $cached[] = $content;
        }

        return $cached;
    }


}