<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 08/12/2015
 * Time: 22:10
 */

namespace Model\User\Thread;


use Everyman\Neo4j\Node;
use Model\Neo4j\GraphManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentThreadManager
{

    /** @var  GraphManager */
    protected $graphManager;

    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    /**
     * @param $id
     * @param $name
     * @param $type
     * @return Thread
     */
    public function buildContentThread($id, $name, $type)
    {
        $thread = new ContentThread($id, $name, $type);

        $tags = $this->getTags($thread->getId());
        $thread->setTags($tags);

        return $thread;
    }

    private function getTags($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->optionalMatch('(thread)-[:FILTERS_BY]->(tag:Tag)')
            ->returns('collect(tag) as tags');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $id . ' not found');
        }

        $tags = array();
        /** @var Node $tagNode */
        foreach ($result->current()->offsetGet('tags') as $tagNode) {
            $tags[] = $tagNode->getProperty('name');
        }

        return $tags;
    }

    public function saveContentThread($filters)
    {
        return null;
    }


}