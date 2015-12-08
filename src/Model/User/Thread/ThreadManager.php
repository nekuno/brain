<?php

namespace Model\User\Thread;


use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Neo4j\GraphManager;
use Model\User\GroupModel;
use Model\User\ProfileModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThreadManager
{
    const LABEL_THREAD = 'Thread';
    const LABEL_THREAD_USERS = 'ThreadUsers';
    const LABEL_THREAD_CONTENT = 'ThreadContent';

    /** @var  GraphManager */
    protected $graphManager;
    /** @var  UsersThreadManager */
    protected $usersThreadManager;
    /** @var  ContentThreadManager */
    protected $contentThreadManager;

    /**
     * ThreadManager constructor.
     * @param GraphManager $graphManager
     * @param UsersThreadManager $um
     * @param ContentThreadManager $cm
     */
    public function __construct(GraphManager $graphManager, UsersThreadManager $um, ContentThreadManager $cm)
    {
        $this->graphManager = $graphManager;
        $this->usersThreadManager = $um;
        $this->contentThreadManager = $cm;
    }

    /**
     * @param $id
     * @return Thread
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getById($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->returns('thread');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $id . ' not found');
        }

        /** @var Node $threadNode */
        $threadNode = $result->current()->offsetGet('thread');

        return $this->buildThread($threadNode);
    }

    /**
     * @param $userId
     * @return array of Thread
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getByUser($userId)
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

    /**
     * Builds a complete Thread object from a neo4j node
     * @param Node $threadNode
     * @return Thread
     * @throws \Exception
     */
    private function buildThread(Node $threadNode)
    {
        $id = $threadNode->getId();

        switch ($category = $this->getCategory($threadNode)) {
            case $this::LABEL_THREAD_USERS: {
                return $this->usersThreadManager->buildUsersThread($id, $threadNode->getProperty('name'));
            }
            case $this::LABEL_THREAD_CONTENT: {
                return $this->contentThreadManager->buildContentThread($id, $threadNode->getProperty('name'), $threadNode->getProperty('type'));
            }
            default :
                throw new \Exception('Thread category ' . $category . ' not found or supported');
        }
    }

    /**
     * @param Node $threadNode
     * @return null|string
     */
    private function getCategory(Node $threadNode)
    {
        $labels = $threadNode->getLabels();

        /** @var Label $label */
        foreach ($labels as $label) {
            if ($label->getName() != ThreadManager::LABEL_THREAD) {
                return $label->getName();
            }
        }

        return null;
    }

    /**
     * Creates an appropiate neo4j node from a set of filters
     * @param $category
     * @param $filters
     * @return null
     */
    public function saveThread($category, $filters)
    {
        switch ($category) {
            case $this::LABEL_THREAD_CONTENT:
                return $this->contentThreadManager->saveContentThread($filters);
            case $this::LABEL_THREAD_USERS:
                return $this->usersThreadManager->saveUsersThread($filters);
            default:
                return null;
        }
    }

}