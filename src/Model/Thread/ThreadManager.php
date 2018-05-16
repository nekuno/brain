<?php

namespace Model\Thread;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\Group\Group;
use Service\Validator\ThreadValidator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThreadManager
{
    const LABEL_THREAD = 'Thread';
    const LABEL_THREAD_USERS = 'ThreadUsers';
    const LABEL_THREAD_CONTENT = 'ThreadContent';
    const SCENARIO_DEFAULT = 'default';
    const SCENARIO_DEFAULT_LITE = 'default_lite';
    const SCENARIO_NONE = 'none';

    static public $scenarios = array(ThreadManager::SCENARIO_DEFAULT, ThreadManager::SCENARIO_DEFAULT_LITE, ThreadManager::SCENARIO_NONE);

    /** @var  GraphManager */
    protected $graphManager;
    /** @var ThreadValidator */
    protected $validator;

    /**
     * ThreadManager constructor.
     * @param GraphManager $graphManager
     * @param ThreadValidator $validator
     */
    public function __construct(
        GraphManager $graphManager,
        ThreadValidator $validator
    ) {
        $this->graphManager = $graphManager;
        $this->validator = $validator;
    }

    /**
     * @param $threadId
     * @return Thread
     * @throws \Exception
     */
    public function getById($threadId)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}');
        $qb->optionalMatch('(thread)-[:IS_FROM_GROUP]->(group:Group)')
            ->returns('thread', 'id(group) AS groupId');
        $qb->setParameter('id', (integer)$threadId);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $threadId . ' not found');
        }

        return $this->build($result->current());
    }

    /**
     * @param $userId
     * @return Thread[]
     * @throws \Exception
     */
    public function getAllByUserId($userId)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(user:User)')
            ->where('user.qnoow_id= {id}')
            ->optionalMatch('(user)-[:HAS_THREAD]->(thread:Thread)')
            ->returns('user, collect(thread) as threads');
        $qb->setParameter('id', (integer)$userId);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('User with id ' . $userId . ' not found');
        }

        /** @var Row $row */
        $row = $result->current();

        $threads = array();
        /** @var Node $threadNode */
        foreach ($row->offsetGet('threads') as $threadNode) {
            $threads[] = $this->buildThread($threadNode);
        }

        return $threads;
    }

    public function build(Row $row)
    {
        $threadNode = $row->offsetGet('thread');
        $thread = $this->buildThread($threadNode);

        if ($groupId = $row->offsetExists('groupId') ? $row->offsetGet('groupId') : null) {
            $thread->setGroupId($groupId);
        };

        return $thread;
    }

    /**
     * Builds a complete Thread object from a neo4j node
     * @param Node $threadNode
     * @return Thread
     * @throws \Exception
     */
    protected function buildThread(Node $threadNode)
    {
        $id = $threadNode->getId();
        $name = $threadNode->getProperty('name');

        switch ($category = $this->getCategory($threadNode)) {
            case $this::LABEL_THREAD_USERS:
                {
                    $thread = new UsersThread($id, $name);
                    break;
                }
            case $this::LABEL_THREAD_CONTENT:
                {
                    $thread = new ContentThread($id, $name);
                    break;
                }
            default :
                throw new \Exception('Thread category ' . $category . ' not found or supported');
        }

        $thread->setRecommendationUrl($this->getRecommendationUrl($thread));
        $thread->setTotalResults($threadNode->getProperty('totalResults'));
        $thread->setCreatedAt($threadNode->getProperty('createdAt'));
        $thread->setUpdatedAt($threadNode->getProperty('updatedAt'));

        //TODO: Change this to isDefault in queries, faster and less dependent with neo4php
        /* @var $label Label */
        foreach ($threadNode->getLabels() as $label) {
            if ($label->getName() == 'ThreadDefault') {
                $thread->setDefault(true);
            }
        }

        return $thread;
    }

    public function create($userId, $data)
    {
        $this->validateOnCreate($data, $userId);

        $name = isset($data['name']) ? $data['name'] : null;
        $category = isset($data['category']) ? $data['category'] : null;

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User{qnoow_id:{userId}})')
            ->create('(thread:' . ThreadManager::LABEL_THREAD . ':' . $category . ')')
            ->set(
                'thread.name = {name}',
                'thread.createdAt = timestamp()',
                'thread.updatedAt = timestamp()'
            )
            ->create('(u)-[:HAS_THREAD]->(thread)');
        //TODO: Remove if controlled from Threadservice
        if (isset($data['default']) && $data['default'] === true) {
            $qb->set('thread :ThreadDefault');
        }
        $qb->returns('id(thread) AS id');
        $qb->setParameters(
            array(
                'name' => $name,
                'userId' => (integer)$userId
            )
        );

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            return null;
        }

        $id = $result->current()->offsetGet('id');
        $thread = $this->getById($id);

        return $thread;
    }

    /**
     * Replaces thread data with $data
     * @param $threadId
     * @param $userId
     * @param $data
     * @return Thread|null
     * @throws \Exception
     */
    public function update($threadId, $userId, $data)
    {
        $this->validateOnUpdate($data, $userId);

        $name = isset($data['name']) ? $data['name'] : null;
        $category = isset($data['category']) ? $data['category'] : null;

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->remove('thread:' . $this::LABEL_THREAD_USERS . ':' . $this::LABEL_THREAD_CONTENT . ':ThreadDefault')
            ->set('thread:' . $category)
            ->set(
                'thread.name = {name}',
                'thread.updatedAt = timestamp()'
            );
        $qb->returns('thread');
        $qb->setParameters(
            array(
                'name' => $name,
                'id' => (integer)$threadId
            )
        );

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            return null;
        }

        $thread = $this->build($result->current());

        return $thread;
    }

    public function setAsDefault($threadId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->with('thread')
            ->limit(1)
            ->setParameter('id', (integer)$threadId);

        $qb->set('thread:ThreadDefault');

        $qb->returns('thread');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            return null;
        }

        $thread = $this->build($result->current());

        return $thread;
    }

    public function deleteById($id)
    {
        $thread = $this->getById($id);

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->optionalMatch('(thread)-[r]-()')
            ->delete('r, thread')
            ->returns('count(r) as amount');
        $qb->setParameter('id', $thread->getId());
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $id . ' not found');
        }

        return $result->current()->offsetGet('amount');
    }



    public function joinToGroup(Thread $thread, Group $group)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {threadId}')
            ->setParameter('threadId', $thread->getId());
        $qb->match('(group:Group)')
            ->where('id(group) = {groupId}')
            ->setParameter('groupId', $group->getId());
        $qb->set('thread:ThreadGroup');
        $qb->merge('(thread)-[:IS_FROM_GROUP]->(group)');
        $qb->returns('thread', 'id(group) AS groupId');
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException(sprintf('Thread with id %s or group with id %s not found', $thread->getId(), $group->getId()));
        }

        return $this->build($result->current());
    }

    private function validateOnCreate($data, $userId)
    {
        $data['userId'] = $userId;
        $this->validator->validateOnCreate($data);
    }

    private function validateOnUpdate($data, $userId)
    {
        $data['userId'] = $userId;
        $this->validator->validateOnUpdate($data);
    }

    /**
     * @param Node $threadNode
     * @return null|string
     */
    private function getCategory(Node $threadNode)
    {
        $id = $threadNode->getId();
        $qb = $this->graphManager->createQueryBuilder();
        $qb->setParameter('id', $id);

        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->returns('labels(thread) as labels');

        $rs = $qb->getQuery()->getResultSet();
        $labels = $rs->current()->offsetGet('labels');
        /** @var Label $label */
        foreach ($labels as $label) {
            if ($label != ThreadManager::LABEL_THREAD && $label != 'ThreadDefault') {
                return $label;
            }
        }

        return null;
    }

    private function getRecommendationUrl(Thread $thread)
    {
        return 'threads/' . $thread->getId() . '/recommendation?offset=20';
    }




}

