<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 08/12/2015
 * Time: 22:06
 */

namespace Model\User\Thread;


use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Neo4j\GraphManager;
use Model\User\GroupModel;
use Model\User\ProfileModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UsersThreadManager
{

    /** @var  GraphManager */
    protected $graphManager;

    /** @var  ProfileModel */
    protected $profileModel;

    /** @var  GroupModel */
    protected $groupModel;

    public function __construct(GraphManager $gm, ProfileModel $pm, GroupModel $groupModel)
    {
        $this->graphManager = $gm;
        $this->profileModel = $pm;
        $this->groupModel = $groupModel;
    }

    /**
     * @param $id
     * @param $name
     * @return UsersThread
     */
    public function buildUsersThread($id, $name)
    {

        $thread = new UsersThread($id, $name);
        $profileFilters = $this->getProfileFilters($thread->getId());
        $thread->setProfileFilters($profileFilters);
        $userFilters = $this->getUserFilters($thread->getId());
        $thread->setUserFilters($userFilters);

        return $thread;
    }

    /**
     * Creates array ready to use as profileFilter from neo4j
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
            ),
            'description' => $threadNode->getProperty('description'));

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

        return array_filter($profileFilters);
    }

    /**
     * Creates array ready to use as userFilter from neo4j
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

    public function saveComplete($id, $filters)
    {

        $profileFilters = isset($filters['profileFilters'])? $filters['profileFilters'] : array();

        $savedProfileFilters = $this->saveProfileFilters($id, $profileFilters);

        $userFilters = isset($filters['userFilters'])? $filters['userFilters'] : array();

        $savedUserFilters = $this->saveUserFilters($id, $userFilters);

        return array(
            'userFilters' => $savedUserFilters,
            'profileFilters' => $savedProfileFilters,
        );
    }

    private function saveProfileFilters($id, $profileFilters)
    {
        return $profileFilters;
    }

    private function saveUserFilters($id, $userFilters)
    {
        return $userFilters;
    }


}