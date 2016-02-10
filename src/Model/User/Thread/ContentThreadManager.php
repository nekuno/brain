<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User\Thread;


use Everyman\Neo4j\Node;
use Model\LinkModel;
use Model\Neo4j\GraphManager;
use Model\User\Filters\FilterContentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function __construct(GraphManager $graphManager, LinkModel $linkModel, FilterContentManager $filterContentManager)
    {
        $this->graphManager = $graphManager;
        $this->linkModel = $linkModel;
        $this->filterContentManager = $filterContentManager;
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
            ->optionalMatch('(thread)-[:RECOMMENDS]->(r:Link)')
            ->returns('collect(r) as cached');
        $qb->setParameters($parameters);
        $result = $qb->getQuery()->getResultSet();

        $cached = array();
        foreach ($result->current()->offsetGet('cached') as $link) {
            $cached[] = $this->linkModel->buildLink($link);
        }

        return $cached;
    }


}