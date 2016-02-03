<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User\Filters;


use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Neo4j\GraphManager;
use Model\User\GroupModel;
use Model\User\ProfileModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FilterManager
{
    protected $fields;

    /**
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * @var ProfileModel
     */
    protected $profileModel;

    /**
     * @var GroupModel
     */
    protected $groupModel;

    public function __construct(array $fields, GraphManager $graphManager, ProfileModel $profileModel, GroupModel $groupModel)
    {
        $this->fields = $fields;
        $this->graphManager = $graphManager;
        $this->profileModel = $profileModel;
        $this->groupModel = $groupModel;
    }

    /**
     * @param $id
     * @return FilterUsers
     */
    public function getFilterUsersById($id)
    {
        $filter = $this->buildFiltersUsers();
        $filter->setProfileFilters($this->getProfileFilters($id));
        $filter->setUsersFilters($this->getUserFilters($id));
        return $filter;
    }

    /**
     * @param FilterUsers $filters
     * @return Node|null
     * @throws \Model\Neo4j\Neo4jException
     */
    public function createFilterUsers(FilterUsers $filters)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->create('(filter:Filter)')
            ->returns('filter');
        $result = $qb->getQuery()->getResultSet();

        $filter = $result->current()->offsetGet('filter');
        if ($filter == null){
            return null;
        }

        return $this->updateFiltersUsers($filters);
    }

    /**
     * @param FilterUsers $filters
     * @return bool
     */
    public function updateFiltersUsers(FilterUsers $filters)
    {
        $userFilters = $filters->getUserFilters();
        $profileFilters = $filters->getProfileFilters();
        
        if (!empty($userFilters)){
            $this->saveUserFilters($userFilters, $filters->getId());
        }
        
        if (!empty($profileFilters)){
            $this->saveProfileFilters($profileFilters, $filters->getId());
        }

        return true;
    }

    /**
     * @return FilterUsers
     */
    public function buildFiltersUsers()
    {
        return new FilterUsers($this->fields);
    }

    private function saveProfileFilters($profileFilters, $id)
    {
        $metadata = $this->profileModel->getMetadata();

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:Filter)')
            ->where('id(filter) = {id}');

        //TODO: More parameters
        foreach ($metadata as $fieldName => $fieldData) {
            switch ($fieldType = $metadata[$fieldName]['type']) {
                case 'text':
                case 'textarea':
                    $qb->remove("filter.$fieldName");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        $qb->set("filter.$fieldName = '$value'");
                    }
                    $qb->with('filter');
                    break;
                case 'birthday':

                    $qb->remove("filter.age_min", "filter.age_max");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        //We do not support only one of these

                        $age = $this->profileModel->getAgeRangeFromBirthdayRange($value);

                        $qb->set('filter.age_min = ' . $age['min']);
                        $qb->set('filter.age_max = ' . $age['max']);

                    }
                    $qb->with('filter');
                    break;
                case 'integer':

                    $fieldNameMin = $fieldName . '_min';
                    $fieldNameMax = $fieldName . '_max';
                    $qb->remove("filter.$fieldNameMin", "filter.$fieldNameMax");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        //We do not support only one of these
                        $min = isset($value['min']) ? (integer)$value['min'] : $metadata[$fieldName]['min'];
                        $max = isset($value['max']) ? (integer)$value['max'] : $metadata[$fieldName]['max'];
                        if ($min) {
                            $qb->set('filter.' . $fieldNameMin . ' = ' . $min);
                        }
                        if ($max) {
                            $qb->set('filter.' . $fieldNameMax . ' = ' . $max);
                        }

                    }
                    $qb->with('filter');
                    break;
                case 'date':

                    break;
                case 'location':
                    //If Location node is shared, this fails (can't delete node with relationships)
                    $qb->optionalMatch('(filter)-[old_loc_rel:FILTERS_BY]->(old_loc_node:Location)')
                        ->delete('old_loc_rel', 'old_loc_node');

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        $distance = (int)$value['distance'];
                        $latitude = (float)$value['location']['latitude'];
                        $longitude = (float)$value['location']['longitude'];
                        $qb->merge("(filter)-[loc_rel:FILTERS_BY{distance:$distance }]->(location:Location)");
                        $qb->set("loc_rel.distance = $distance");
                        $qb->set("location.latitude = $latitude");
                        $qb->set("location.longitude = $longitude");
                    }
                    $qb->with('filter');
                    break;
                case 'boolean':
                    $qb->remove("filter.$fieldName");

                    if (isset($profileFilters[$fieldName])) {
                        $qb->set("filter.$fieldName = true");
                    }
                    $qb->with('filter');
                    break;
                case 'choice':
                case 'double_choice':
                    $profileLabelName = ucfirst($fieldName);
                    $qb->optionalMatch("(filter)-[old_po_rel:FILTERS_BY]->(:$profileLabelName)")
                        ->delete("old_po_rel");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        foreach ($value as $singleValue) {
                            $qb->merge(" (option$fieldName$singleValue:$profileLabelName{id:'$singleValue'})");
                            $qb->merge(" (filter)-[:FILTERS_BY]->(option$fieldName$singleValue)");
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'tags':
                    $tagLabelName = ucfirst($fieldName);
                    $qb->optionalMatch("(filter)-[old_tag_rel:FILTERS_BY]->(:$tagLabelName)")
                        ->delete("old_tag_rel");
                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        $qb->merge("(tag$fieldName:$tagLabelName{name:'$value'})");
                        $qb->merge("(filter)-[:FILTERS_BY]->(tag$fieldName)");
                    }
                    $qb->with('filter');
                    break;
            }
        }

        $qb->returns('filter');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return null;
        }

        return $result->current()->offsetGet('filter');

    }

    //TODO: UserFilters use metadata
    private function saveUserFilters($userFilters, $id)
    {

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:Filter)')
            ->where('id(filter) = {id}');

        //Implement metadata format here if using more than "groups" userFilter
        if (isset($userFilters['groups'])) {
            foreach ($userFilters['groups'] as $group) {
                $qb->match("(group$group:Group)")
                    ->where("id(group$group) = $group")
                    ->merge("(filter)-[:FILTERS_BY]->(group$group)");
            }
        }

        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return null ;
        }


        return;
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
        $qb->match('(filter:Filter)')
            ->where('id(filter) = {id}')
            ->optionalMatch('(filter)-[:FILTERS_BY]->(po:ProfileOption)')
            ->optionalMatch('(filter)-[loc_rel:FILTERS_BY]->(loc:Location)')
            ->returns('filter, collect(distinct po) as options, loc, loc_rel');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('filter with id ' . $id . ' not found');
        }

        /** @var Row $row */
        $row = $result->current();
        /** @var Node $filterNode */
        $filterNode = $row->offsetGet('filter');
        $options = $row->offsetGet('options');

        $profileFilters = $this->buildProfileOptions($options, $filterNode);

        $profileFilters += array(
            'birthday' => $this->profileModel->getBirthdayRangeFromAgeRange(
                $filterNode->getProperty('age_min'),
                $filterNode->getProperty('age_max')),
            'description' => $filterNode->getProperty('description'));
        $height = array(
            'min' => $filterNode->getProperty('height_min'),
            'max' => $filterNode->getProperty('height_max')
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
     * Quite similar to ProfileModel->buildProfileOptions
     * @param \ArrayAccess $options
     * @param Node $filterNode
     * @return array
     */
    private function buildProfileOptions(\ArrayAccess $options, Node $filterNode)
    {
        $optionsResult = array();
        /* @var Node $option */
        foreach ($options as $option) {
            $labels = $option->getLabels();
            /* @var Relationship $relationship */
            //TODO: Can get slow (increments with filter amount), change to cypher specifying id from beginning
            $relationships = $option->getRelationships('FILTERS_BY', Relationship::DirectionIn);
            foreach ($relationships as $relationship) {
                if ($relationship->getStartNode()->getId() === $option->getId() &&
                    $relationship->getEndNode()->getId() === $filterNode->getId()
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

    /**
     * Creates array ready to use as userFilter from neo4j
     * @param $id
     * @return array
     * @throws \Model\Neo4j\Neo4jException
     */
    private function getUserFilters($id)
    {

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:filter)')
            ->where('id(filter) = {id}')
            ->optionalMatch('(filter)-[:FILTERS_BY]->(group:Group)')
            ->returns('collect(group) as groups');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('filter with id ' . $id . ' not found');
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


}