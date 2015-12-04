<?php

namespace Model\User\Thread;


use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Neo4j\GraphManager;
use Model\User\ProfileModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThreadManager
{
    /** @var  GraphManager */
    protected $graphManager;

    /** @var  ProfileModel */
    protected $profileModel;

    protected function buildUsersThread($id, $name, $category)
    {

        $thread = new UsersThread($id, $name, $category);
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
            ->where('id(t) = {id}')
            ->optionalMatch('(thread)-[:FILTERS_BY]-(po:ProfileOption)')
            ->optionalMatch('(thread)-[loc_rel:FILTERS_BY]-(loc:Location)')
            ->returns('thread, collect(po) as options, loc, loc_rel');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1){
            throw new NotFoundHttpException('Thread with id '.$id.' not found');
        }

        /** @var Row $row */
        $row = $result->current();
        /** @var Node $thread */
        $thread = $row->offsetGet('thread');
        $options = $row->offsetGet('options');
        $options = $this->profileModel->buildProfileOptions($thread, $options, 'FILTERS_BY');

        $options += array(  'ageRange' => array(
                                'age_min' => $thread->getProperty('age_min'),
                                'age_max' => $thread->getProperty('age_max')),
                            'heightRange' => array(
                                'height_min' => $thread->getProperty('height_min'),
                                'height_max' => $thread->getProperty('height_max')
                            ));

        /** @var Node $location */
        $location = $row->offsetGet('location');
        if ($location !== false){
            /** @var Relationship $locationRelationship */
            $locationRelationship = $row->offsetGet('loc_rel');
            $options += array( 'location' => array(
                'distance' => $locationRelationship->getProperty('distance'),
                'location' => array(
                    'latitude' => $location->getProperty('latitude'),
                    'longitude' => $location->getProperty('longitude'),
                    'address' => $location->getProperty('address'),
                )
            ));
        }

        return $options;
    }

    private function getUserFilters($id)
    {
        return array();
    }

}