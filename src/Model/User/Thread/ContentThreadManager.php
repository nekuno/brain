<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 08/12/2015
 * Time: 22:10
 */

namespace Model\User\Thread;


use Everyman\Neo4j\Node;
use Model\LinkModel;
use Model\Neo4j\GraphManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentThreadManager
{

    /** @var $graphManager GraphManager */
    protected $graphManager;
    /** @var $linkModel LinkModel */
    protected $linkModel;

    public function __construct(GraphManager $graphManager, LinkModel $linkModel)
    {
        $this->graphManager = $graphManager;
        $this->linkModel = $linkModel;
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

        $tag = $this->getTag($thread->getId());
        $thread->setTag($tag);

        return $thread;
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Model\Neo4j\Neo4jException
     */
    private function getTag($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->optionalMatch('(thread)-[:FILTERS_BY]->(tag:Tag)')
            ->returns('tag');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $id . ' not found');
        }

        /** @var Node $tagNode */
        $tagNode = $result->current()->offsetGet('tag');
        if ($tagNode) {
            return $tagNode->getProperty('name');
        }

        return null;
    }

    /**
     * @param $id
     * @param array $filters Complete filters to filter from
     */
    public function update($id, $filters)
    {
        $type = isset($filters['type']) ? $filters['type'] : 'Link';
        $this->saveType($id, $type);

        $tag = isset($filters['tag']) ? $filters['tag'] : null;
        $this->saveTag($id, $tag);
    }

    private function saveType($id, $type)
    {
        //TODO: Validate

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->set('thread.type = {type}')
            ->returns('thread');
        $qb->setParameters(array(
            'id' => (integer)$id,
            'type' => $type,
        ));
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $id . ' not found');
        }

    }

    private function saveTag($id, $tag)
    {
        //TODO: Validate

        if (!$tag) {
            return;
        }

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->optionalMatch('(thread)-[old_tag_rel:FILTERS_BY]->(:Tag')
            ->delete('old_tag_rel')
            ->with('thread')
            ->match('(tag:Tag{name: {tagname} })')
            ->merge('(thread)-[:FILTERS_BY]->(tag)')
            ->returns('thread');
        $qb->setParameters(array(
            'id' => (integer)$id,
            'tagname' => $tag,
        ));
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $id . ' or tag with name ' . $tag . ' not found');
        }
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