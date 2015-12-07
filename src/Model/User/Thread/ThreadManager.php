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

    /** @var  ProfileModel */
    protected $profileModel;

    /** @var  GroupModel */
    protected $groupModel;

    /**
     * ThreadManager constructor.
     * @param GraphManager $graphManager
     * @param ProfileModel $profileModel
     * @param GroupModel $groupModel
     */
    public function __construct(GraphManager $graphManager, ProfileModel $profileModel, GroupModel $groupModel)
    {
        $this->graphManager = $graphManager;
        $this->profileModel = $profileModel;
        $this->groupModel = $groupModel;
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
     * @param $id
     * @param $name
     * @return UsersThread
     */
    protected function buildUsersThread($id, $name)
    {

        $thread = new UsersThread($id, $name);
        $profileFilters = $this->getProfileFilters($thread->getId());
        $thread->setProfileFilters($profileFilters);
        $userFilters = $this->getUserFilters($thread->getId());
        $thread->setUserFilters($userFilters);

        return $thread;
    }

    /**
     * Creates array ready to use as profileFilter from Thread id
     * @param $id
     * @return array ready to use in recommendation
     * @throws \Model\Neo4j\Neo4jException
     */
    private function getProfileFilters($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->optionalMatch('(thread)-[:FILTERS_BY]-(po:ProfileOption)')
            ->optionalMatch('(thread)-[loc_rel:FILTERS_BY]-(loc:Location)')
            ->returns('thread, collect(distinct po) as options, loc, loc_rel');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $id . ' not found');
        }

        /** @var Row $row */
        $row = $result->current();
        /** @var Node $threadNode */
        $threadNode = $row->offsetGet('thread');
        $options = $row->offsetGet('options');

        $profileFilters = $this->profileModel->buildProfileOptions($options, $threadNode, 'FILTERS_BY', Relationship::DirectionIn);

        $profileFilters += array(
            'birthday' => $this->profileModel->getBirthdayRangeFromAgeRange(
                $threadNode->getProperty('age_min'),
                $threadNode->getProperty('age_max')),
            'height' => array(
                'min' => $threadNode->getProperty('height_min'),
                'max' => $threadNode->getProperty('height_max')
            ));

        /** @var Node $location */
        $location = $row->offsetGet('loc');
        if ($location instanceof Node) {

            /** @var Relationship $locationRelationship */
            $locationRelationship = $row->offsetGet('loc_rel');
            $profileFilters += array('location' => array(
                'distance' => $locationRelationship->getProperty('distance'),
                'location' => array(
                    'latitude' => $location->getProperty('latitude'),
                    'longitude' => $location->getProperty('longitude'),
                    'address' => $location->getProperty('address'),
                )
            ));
        }

        return $profileFilters;
    }

    /**
     * @param $id
     * @return array
     * @throws \Model\Neo4j\Neo4jException
     */
    private function getUserFilters($id)
    {

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->optionalMatch('(thread)-[:FILTERS_BY]->(group:Group)')
            ->returns('collect(group) as groups');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $id . ' not found');
        }

        $userFilters = array();

        /** @var Row $row */
        $row = $result->current();

        $userFilters['groups'] = array();
        foreach ($row->offsetGet('groups') as $group) {
            $userFilters['groups'] = $this->groupModel->buildGroup($group, null, null);
        }

        return $userFilters;
    }

    /**
     * @param Node $threadNode
     * @return null
     */
    private function getType(Node $threadNode)
    {
        $labels = $threadNode->getLabels();

        /** @var Label $label */
        foreach ($labels as $label) {
            if ($label->getName() != $this::LABEL_THREAD) {
                return $label->getName();
            }
        }

        return null;
    }

    /**
     * @param $id
     * @param $name
     * @return Thread
     */
    private function buildContentThread($id, $name)
    {
        $thread = new Thread($id, $name);

        return $thread;
    }

    /**
     * @param Node $threadNode
     * @return Thread
     * @throws \Exception
     */
    private function buildThread(Node $threadNode)
    {
        $id = $threadNode->getId();

        switch ($type = $this->getType($threadNode)) {
            case $this::LABEL_THREAD_USERS: {
                return $this->buildUsersThread($id, $threadNode->getProperty('name'));
            }
            case $this::LABEL_THREAD_CONTENT: {
                return $this->buildContentThread($id, $threadNode->getProperty('name'));
            }
            default :
                throw new \Exception('Thread type ' . $type . ' not found or supported');
        }
    }

}