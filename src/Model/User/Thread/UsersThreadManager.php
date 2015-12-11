<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 08/12/2015
 * Time: 22:06
 */

namespace Model\User\Thread;


use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Neo4j\GraphManager;
use Model\User\GroupModel;
use Model\User\ProfileModel;
use Model\UserModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UsersThreadManager
{

    /** @var  GraphManager */
    protected $graphManager;

    /** @var  ProfileModel */
    protected $profileModel;

    /** @var  GroupModel */
    protected $groupModel;

    /** @var UserModel */
    protected $userModel;

    public function __construct(GraphManager $gm, ProfileModel $pm, GroupModel $groupModel, UserModel $userModel)
    {
        $this->graphManager = $gm;
        $this->profileModel = $pm;
        $this->groupModel = $groupModel;
        $this->userModel = $userModel;
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
            ->optionalMatch('(thread)-[:FILTERS_BY]->(po:ProfileOption)')
            ->optionalMatch('(thread)-[loc_rel:FILTERS_BY]->(loc:Location)')
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

        $profileFilters = $this->buildProfileOptions($options, $threadNode);

        $profileFilters += array(
            'birthday' => $this->profileModel->getBirthdayRangeFromAgeRange(
                $threadNode->getProperty('age_min'),
                $threadNode->getProperty('age_max')),
            'description' => $threadNode->getProperty('description'));
        $height = array(
            'min' => $threadNode->getProperty('height_min'),
            'max' => $threadNode->getProperty('height_max')
        );
        $height = array_filter($height);
        if (!empty($height)) {
            $profileFilters['height'] = $height;
        }
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

    public function update($id, $filters)
    {

        $profileFilters = isset($filters['profileFilters']) ? $filters['profileFilters'] : array();
        $this->saveProfileFilters($id, $profileFilters);

        $userFilters = isset($filters['userFilters']) ? $filters['userFilters'] : array();
        $this->saveUserFilters($id, $userFilters);

    }

    private function saveProfileFilters($id, $profileFilters)
    {

        $metadata = $this->profileModel->getMetadata();

        //TODO: Validation

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:' . ThreadManager::LABEL_THREAD_USERS . ')')
            ->where('id(thread) = {id}');

        //TODO: More parameters
        foreach ($metadata as $fieldName => $fieldData) {
            switch ($fieldType = $metadata[$fieldName]['type']) {
                case 'text':
                case 'textarea':
                    $qb->remove("thread.$fieldName");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        $qb->set("thread.$fieldName = '$value'");
                    }
                    $qb->with('thread');
                    break;
                case 'birthday':

                    $qb->remove("thread.age_min", "thread.age_max");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        //We do not support only one of these

                        $age = $this->profileModel->getAgeRangeFromBirthdayRange($value);

                        $qb->set('thread.age_min = ' . $age['min']);
                        $qb->set('thread.age_max = ' . $age['max']);

                    }
                    $qb->with('thread');
                    break;
                case 'integer':

                    $fieldNameMin = $fieldName . '_min';
                    $fieldNameMax = $fieldName . '_max';
                    $qb->remove("thread.$fieldNameMin", "thread.$fieldNameMax");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        //We do not support only one of these
                        $min = isset($value['min']) ? (integer)$value['min'] : $metadata[$fieldName]['min'];
                        $max = isset($value['max']) ? (integer)$value['max'] : $metadata[$fieldName]['max'];
                        if ($min) {
                            $qb->set('thread.' . $fieldNameMin . ' = ' . $min);
                        }
                        if ($max) {
                            $qb->set('thread.' . $fieldNameMax . ' = ' . $max);
                        }

                    }
                    $qb->with('thread');
                    break;
                case 'date':

                    break;
                case 'location':
                    //If Location node is shared, this fails (can't delete node with relationships)
                    $qb->optionalMatch('(thread)-[old_loc_rel:FILTERS_BY]->(old_loc_node:Location)')
                        ->delete('old_loc_rel', 'old_loc_node');

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        $distance = (int)$value['distance'];
                        $latitude = (float)$value['location']['latitude'];
                        $longitude = (float)$value['location']['longitude'];
                        $qb->merge("(thread)-[loc_rel:FILTERS_BY{distance:$distance }]->(location:Location)");
                        $qb->set("loc_rel.distance = $distance");
                        $qb->set("location.latitude = $latitude");
                        $qb->set("location.longitude = $longitude");
                    }
                    $qb->with('thread');
                    break;
                case 'boolean':
                    $qb->remove("thread.$fieldName");

                    if (isset($profileFilters[$fieldName])) {
                        $qb->set("thread.$fieldName = true");
                    }
                    $qb->with('thread');
                    break;
                case 'choice':
                case 'double_choice':
                    $profileLabelName = ucfirst($fieldName);
                    $qb->optionalMatch("(thread)-[old_po_rel:FILTERS_BY]->(:$profileLabelName)")
                        ->delete("old_po_rel");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        foreach ($value as $singleValue) {
                            $qb->merge(" (option$fieldName$singleValue:$profileLabelName{id:'$singleValue'})");
                            $qb->merge(" (thread)-[:FILTERS_BY]->(option$fieldName$singleValue)");
                        }
                    }
                    $qb->with('thread');
                    break;
                case 'tags':
                    $tagLabelName = ucfirst($fieldName);
                    $qb->optionalMatch("(thread)-[old_tag_rel:FILTERS_BY]->(:$tagLabelName)")
                        ->delete("old_tag_rel");
                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        $qb->merge("(tag$fieldName:$tagLabelName{name:'$value'})");
                        $qb->merge("(thread)-[:FILTERS_BY]->(tag$fieldName)");
                    }
                    $qb->with('thread');
                    break;
            }
        }

        $qb->returns('thread');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return null;
        }


        return $profileFilters;
    }

    private function saveUserFilters($id, $userFilters)
    {
        //TODO: Validation
        if (empty($userFilters)) {
            return;
        }

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:' . ThreadManager::LABEL_THREAD_USERS . ')')
            ->where('id(thread) = {id}');

        //Implement metadata format here if using more than "groups" userFilter
        if (isset($userFilters['groups'])) {
            foreach ($userFilters['groups'] as $group) {
                $qb->match("(group$group:Group)")
                    ->where("id(group$group) = $group")
                    ->merge("(thread)-[:FILTERS_BY]->(group$group)");
            }
        }

        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return;
        }


        return;
    }


    /**
     * Quite similar to ProfileModel->buildProfileOptions
     * @param \ArrayAccess $options
     * @param Node $threadNode
     * @return array
     */
    private function buildProfileOptions(\ArrayAccess $options, Node $threadNode)
    {
        $optionsResult = array();
        /* @var Node $option */
        foreach ($options as $option) {
            $labels = $option->getLabels();
            /* @var Relationship $relationship */
            //TODO: Can get slow (increments with thread amount), change to cypher specifying id from beginning
            $relationships = $option->getRelationships('FILTERS_BY', Relationship::DirectionIn);
            foreach ($relationships as $relationship) {
                if ($relationship->getStartNode()->getId() === $option->getId() &&
                    $relationship->getEndNode()->getId() === $threadNode->getId()
                ) {
                    break;
                }
            }
            /* @var Label $label */
            foreach ($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileOption') {
                    $typeName = $this->profileModel->labelToType($label->getName());
                    $optionsResult[$typeName] = empty($optionsResult[$typeName]) ? array($option->getProperty('id')) :
                        array_merge($optionsResult[$typeName], array($option->getProperty('id')));
                    $detail = $relationship->getProperty('detail');
                    if (!is_null($detail)) {
                        $optionsResult[$typeName] = array();
                        $optionsResult[$typeName]['choice'] = $option->getProperty('id');
                        $optionsResult[$typeName]['detail'] = $detail;
                    }
                }
            }
        }

        return $optionsResult;
    }

    public function getCached(Thread $thread)
    {
        $parameters = array(
            'id' => $thread->getId(),
        );

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->match('(thread)-[:RECOMMENDS]->(u:User)')
            ->returns('u');
        $qb->setParameters($parameters);
        $result = $qb->getQuery()->getResultSet();

        $cached = array();
        foreach ($result as $row) {
            $cached[] = $this->userModel->build($row);
        }

        return $cached;
    }


}