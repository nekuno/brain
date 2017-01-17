<?php

namespace Model\User\Filters;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Neo4j\GraphManager;
use Model\User\ProfileFilterModel;
use Model\User\UserFilterModel;
use Service\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FilterUsersManager
{
    /**
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * @var ProfileFilterModel
     */
    protected $profileFilterModel;

    /**
     * @var UserFilterModel
     */
    protected $userFilterModel;

    /**
     * @var Validator
     */
    protected $validator;

    public function __construct(GraphManager $graphManager, ProfileFilterModel $profileFilterModel, UserFilterModel $userFilterModel, Validator $validator)
    {
        $this->graphManager = $graphManager;
        $this->profileFilterModel = $profileFilterModel;
        $this->userFilterModel = $userFilterModel;
        $this->validator = $validator;
    }

    public function getFilterUsersByThreadId($id)
    {
        $filterId = $this->getFilterUsersIdByThreadId($id);

        return $this->getFilterUsersById($filterId);
    }

    /**
     * @param FilterUsers $filters
     * @return Node|null
     * @throws \Model\Neo4j\Neo4jException
     */
    public function createFilterUsers(FilterUsers $filters)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->create('(filter:Filter:FilterUsers)')
            ->returns('filter');
        $result = $qb->getQuery()->getResultSet();

        $filter = $result->current()->offsetGet('filter');
        if ($filter == null) {
            return null;
        }

        return $this->updateFiltersUsers($filters);
    }

    public function updateFilterUsersByThreadId($id, $filtersArray)
    {
        $filters = $this->buildFiltersUsers();

        $filterId = $this->getFilterUsersIdByThreadId($id);
        $filters->setId($filterId);

        $splitFilters = $this->profileFilterModel->splitFilters($filtersArray);

        if (isset($splitFilters['profileFilters']) && !empty($splitFilters['profileFilters'])) {
            $filters->setProfileFilters($splitFilters['profileFilters']);
        }

        if (isset($splitFilters['userFilters']) && !empty($splitFilters['userFilters'])) {
            $filters->setUsersFilters($splitFilters['userFilters']);
        }

        $this->updateFiltersUsers($filters);

        return $filters;
    }

    public function updateFilterUsersByGroupId($id, $filtersArray)
    {
        $filters = $this->buildFiltersUsers();

        $filterId = $this->getFilterUsersIdByGroupId($id);
        $filters->setId($filterId);

        if (isset($filtersArray['profileFilters'])) {
            $filters->setProfileFilters($filtersArray['profileFilters']);
        }

        if (isset($filtersArray['userFilters'])) {
            $filters->setUsersFilters($filtersArray['userFilters']);
        }

        $this->updateFiltersUsers($filters);

        return $filters;
    }

    /**
     * @param $id
     * @return FilterUsers
     */
    public function getFilterUsersById($id)
    {
        $filter = $this->buildFiltersUsers();
        $filter->setUsersFilters(array_merge($this->getUserFilters($id), $this->getProfileFilters($id)));

        return $filter;
    }

    public function validateFilterUsers(array $filters, $userId = null)
    {
        $filters = $this->profileFilterModel->splitFilters($filters);

        if (isset($filters['profileFilters'])) {
            $this->validator->validateEditFilterProfile($filters['profileFilters'], $this->profileFilterModel->getChoiceOptionIds());
        }

        if (isset($filters['userFilters'])) {
            $this->validator->validateEditFilterUsers($filters['userFilters'], $this->userFilterModel->getChoiceOptionIds($userId));
        }
    }

    /**
     * @param FilterUsers $filters
     * @return bool
     */
    protected function updateFiltersUsers(FilterUsers $filters)
    {
        $userFilters = $filters->getUserFilters();
        $profileFilters = $filters->getProfileFilters();

        $this->saveUserFilters($userFilters, $filters->getId());
        $this->saveProfileFilters($profileFilters, $filters->getId());

        return true;
    }

    /**
     * @return FilterUsers
     */
    protected function buildFiltersUsers()
    {
        return new FilterUsers();
    }

    protected function getFilterUsersIdByThreadId($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->with('thread')
            ->merge('(thread)-[:HAS_FILTER]->(filter:Filter:FilterUsers)')
            ->returns('id(filter) as filterId');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return null;
        }

        return $result->current()->offsetGet('filterId');
    }

    protected function getFilterUsersIdByGroupId($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(group:Group)')
            ->where('id(group) = {id}')
            ->with('group')
            ->merge('(group)-[:HAS_FILTER]->(filter:Filter:FilterUsers)')
            ->returns('id(filter) as filterId');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return null;
        }

        return $result->current()->offsetGet('filterId');
    }

    public function getByGroupAndUser($groupId, $userId)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(group:Group)')
            ->where('id(group) = groupId}')
            ->with('group')
            ->setParameter('groupId', (int)$groupId);
        $qb->match('(user:User{qnoow_id:{userId}})')
            ->with('group', 'user')
            ->setParameter('userId', (int)$userId);

        $qb->match('(user)');
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return null;
        }

        return $result->current()->offsetGet('filterId');
    }

    private function saveProfileFilters($profileFilters, $id)
    {
        $profileOptions = $this->profileFilterModel->getChoiceOptionIds();
        $this->validator->validateEditFilterProfile($profileFilters, $profileOptions);

        $metadata = $this->profileFilterModel->getMetadata();

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:FilterUsers)')
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
                //TODO: Refactor this and integer_range into saving and loading arrays to the Node
                case 'birthday_range':

                    $qb->remove("filter.age_min", "filter.age_max");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        if (isset($value['min']) && null !== $value['min']){
                            $qb->set('filter.age_min = ' . $value['min']);
                        }
                        if (isset($value['max']) && null !== $value['max']){
                            $qb->set('filter.age_max = ' . $value['max']);
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'integer_range':

                    $fieldNameMin = $fieldName . '_min';
                    $fieldNameMax = $fieldName . '_max';
                    $qb->remove("filter.$fieldNameMin", "filter.$fieldNameMax");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        $min = isset($value['min']) ? (integer)$value['min'] : null;
                        $max = isset($value['max']) ? (integer)$value['max'] : null;
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
                case 'location_distance':
                    //If Location node is shared, this fails (can't delete node with relationships)
                    $qb->optionalMatch('(filter)-[old_loc_rel:FILTERS_BY]->(old_loc_node:Location)')
                        ->delete('old_loc_rel', 'old_loc_node');

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        $qb->setParameters(array(
                            'distance' =>(int)$value['distance'],
                            'latitude' => (float)$value['location']['latitude'],
                            'longitude' => (float)$value['location']['longitude'],
                            'address' => $value['location']['address'],
                            'locality' => $value['location']['locality'],
                            'country' => $value['location']['country'],
                        ));
                        $qb->merge("(filter)-[loc_rel:FILTERS_BY{distance:{distance} }]->(location:Location)");
                        $qb->set("loc_rel.distance = {distance}");
                        $qb->set("location.latitude = {latitude}");
                        $qb->set("location.longitude = {longitude}");
                        $qb->set("location.address = {address}");
                        $qb->set("location.locality = {locality}");
                        $qb->set("location.country = {country}");
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
                    $profileLabelName = $this->profileFilterModel->typeToLabel($fieldName);
                    $qb->optionalMatch("(filter)-[old_po_rel:FILTERS_BY]->(:$profileLabelName)")
                        ->delete("old_po_rel");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        $qb->merge(" (option$fieldName:$profileLabelName{id:'$value'})");
                        $qb->merge(" (filter)-[:FILTERS_BY]->(option$fieldName)");
                    }
                    $qb->with('filter');
                    break;
                case 'double_multiple_choices':
                    $profileLabelName = $this->profileFilterModel->typeToLabel($fieldName);
                    $qb->optionalMatch("(filter)-[old_po_rel:FILTERS_BY]->(:$profileLabelName)")
                        ->delete("old_po_rel");
                    if (isset($profileFilters[$fieldName])) {
                        $counter = 0;
                        foreach ($profileFilters[$fieldName] as $value) {
                            $choice = $value['choice'];
                            $detail = isset($value['detail']) ? $value['detail'] : '';
                            $qb->merge(" (option$fieldName$counter:$profileLabelName{id:'$choice'})");
                            $qb->merge(" (filter)-[po_rel$fieldName$counter:FILTERS_BY]->(option$fieldName$counter)")
                                ->set(" po_rel$fieldName$counter.detail = {detail$fieldName$counter}");
                            $qb->setParameter("detail$fieldName$counter", $detail);
                            $counter++;
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'multiple_choices':
                    $profileLabelName = $this->profileFilterModel->typeToLabel($fieldName);
                    $qb->optionalMatch("(filter)-[old_po_rel:FILTERS_BY]->(:$profileLabelName)")
                        ->delete("old_po_rel");

                    if (isset($profileFilters[$fieldName])) {
                        $counter = 0;
                        foreach ($profileFilters[$fieldName] as $value) {
                            $qb->merge(" (option$fieldName$counter:$profileLabelName{id:'$value'})");
                            $qb->merge(" (filter)-[:FILTERS_BY]->(option$fieldName$counter)");
                            $counter++;
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'tags':
                    $tagLabelName = ucfirst($fieldName);
                    $qb->optionalMatch("(filter)-[old_tag_rel:FILTERS_BY]->(:$tagLabelName)")
                        ->delete("old_tag_rel");
                    if (isset($profileFilters[$fieldName])) {
                        foreach ($profileFilters[$fieldName] as $value) {
                            $qb->merge("(tag$fieldName$value:$tagLabelName{name:'$value'})");
                            $qb->merge("(filter)-[:FILTERS_BY]->(tag$fieldName$value)");
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'tags_and_choice':
                    $tagLabelName = ucfirst($fieldName);
                    $qb->optionalMatch("(filter)-[old_tag_rel:FILTERS_BY]->(:$tagLabelName)")
                        ->delete("old_tag_rel");

                    if (isset($profileFilters[$fieldName])) {
                        foreach ($profileFilters[$fieldName] as $value) {
                            $tag = $fieldName === 'language' ?
                                $this->profileFilterModel->getLanguageFromTag($value['tag']) :
                                $value['tag'];
                            $choice = isset($value['choice']) ? $value['choice'] : '';

                            $qb->merge("(tag$fieldName$tag:$tagLabelName:ProfileTag{name:'$tag'})");
                            $qb->merge("(filter)-[tag_rel$fieldName$tag:FILTERS_BY]->(tag$fieldName$tag)")
                                ->set("tag_rel$fieldName$tag.detail = {detail$fieldName$tag}");
                            $qb->setParameter("detail$fieldName$tag", $choice);
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'tags_and_multiple_choices':
                    $tagLabelName = ucfirst($fieldName);
                    $qb->optionalMatch("(filter)-[old_tag_rel:FILTERS_BY]->(:$tagLabelName)")
                        ->delete("old_tag_rel");

                    if (isset($profileFilters[$fieldName])) {
                        foreach ($profileFilters[$fieldName] as $value) {
                            $tag = $fieldName === 'language' ?
                                $this->profileFilterModel->getLanguageFromTag($value['tag']) :
                                $value['tag'];

                            $choices = isset($value['choices']) ? $value['choices'] : array();
                            $qb->merge("(tag$fieldName$tag:$tagLabelName:ProfileTag{name:'$tag'})");
                            $qb->merge("(filter)-[tag_rel$fieldName$tag:FILTERS_BY]->(tag$fieldName$tag)")
                                ->set("tag_rel$fieldName$tag.detail = {detail$fieldName$tag}");
                            $qb->setParameter("detail$fieldName$tag", $choices);
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'order':
                    $qb->remove("filter.order");

                    if (isset($profileFilters[$fieldName])) {
                        $value = $profileFilters[$fieldName];
                        if (isset($value['order']) && null !== $value['order']){
                            $qb->set('filter.order = "' . $value['order'] . '"');
                        }
                    }
                    $qb->with('filter');
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

    private function saveUserFilters($userFilters, $id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:FilterUsers)')
            ->where('id(filter) = {id}');

        $qb->optionalMatch('(filter)-[old_rel_group:FILTERS_BY]->(:Group)')
            ->delete('old_rel_group')
            ->with('filter');

        if (isset($userFilters['groups'])) {
            foreach ($userFilters['groups'] as $group) {
                $qb->match("(group$group:Group)")
                    ->where("id(group$group) = $group")
                    ->merge("(filter)-[:FILTERS_BY]->(group$group)")
                    ->with('filter');
            }
            unset($userFilters['groups']);
        }

        $metadata = $this->userFilterModel->getMetadata();

        foreach ($metadata as $fieldName => $fieldValue) {
            switch ($fieldValue['type']) {
                case 'order':
                    $qb->remove("filter.$fieldName");

                    if (isset($userFilters[$fieldName])) {
                        $value = $userFilters[$fieldName];
                        $qb->set('filter.' . $fieldName . ' = "' . $value . '"');
                    }
                    $qb->with('filter');
                    break;
                //single_integer used in Social
                case 'single_integer':
                case 'integer':
                    $qb->remove("filter.$fieldName");

                    if (isset($userFilters[$fieldName])) {
                        $value = $userFilters[$fieldName];
                        $qb->set('filter.' . $fieldName . ' = ' . $value);
                    }
                    $qb->with('filter');
                    break;
                default:
                    break;
            }
        }

        $qb->setParameter('id', (integer)$id);
        $qb->returns('filter');
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return null;
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
        //TODO: Refactor this into metadata
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:FilterUsers)')
            ->where('id(filter) = {id}')
            ->optionalMatch('(filter)-[:FILTERS_BY]->(po:ProfileOption)')
            ->optionalMatch('(filter)-[:FILTERS_BY]->(pt:ProfileTag)')
            ->optionalMatch('(filter)-[loc_rel:FILTERS_BY]->(loc:Location)')
            ->returns('filter, collect(distinct po) as options, collect(distinct pt) as tags, loc, loc_rel');
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
        $tags = $row->offsetGet('tags');

        $profileFilters = $this->buildProfileOptions($options, $filterNode);
        $profileFilters += $this->buildTags($tags, $filterNode);

        if ($filterNode->getProperty('age_min') || $filterNode->getProperty('age_max')) {
            $profileFilters += array(
                'birthday' => array(
                    'min' => $filterNode->getProperty('age_min'),
                    'max' => $filterNode->getProperty('age_max')
                ),
                'description' => $filterNode->getProperty('description')
            );
        }

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
            $profileFilters += array(
                'location' => array(
                    'distance' => $locationRelationship->getProperty('distance'),
                    'location' => array(
                        'latitude' => $location->getProperty('latitude'),
                        'longitude' => $location->getProperty('longitude'),
                        'address' => $location->getProperty('address'),
                        'locality' => $location->getProperty('locality'),
                        'country' => $location->getProperty('country'),
                    )
                )
            );
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
        $filterMetadata = $this->profileFilterModel->getFilters();
        $optionsResult = array();
        /* @var Node $option */
        foreach ($options as $option) {
            $labels = $option->getLabels();
            $relationship = $this->getFilterRelationshipFromNode($option, $filterNode->getId());
            /* @var Label $label */
            foreach ($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileOption') {
                    $typeName = $this->profileFilterModel->labelToType($label->getName());
                    $metadataValues = isset($filterMetadata[$typeName]) ? $filterMetadata[$typeName] : array();

                    switch ($metadataValues['type']) {
                        case 'double_multiple_choices':
                            $detail = $relationship->getProperty('detail');
                            $choiceArray = array('choice' => $option->getProperty('id'), 'detail' => $detail);
                            $optionsResult[$typeName] = isset($optionsResult[$typeName]) && is_array($optionsResult[$typeName]) ?
                                array_merge($optionsResult[$typeName], array($choiceArray))
                                : array($choiceArray);
                            break;
                        case 'double_choice':
                            $detail = $relationship->getProperty('detail');
                            $choiceArray = array('choice' => $option->getProperty('id'), 'detail' => $detail);
                            $optionsResult[$typeName] = $choiceArray;
                            break;
                        default:
                            $optionsResult[$typeName] = empty($optionsResult[$typeName]) ? array($option->getProperty('id')) :
                                array_merge($optionsResult[$typeName], array($option->getProperty('id')));
                            break;
                    }
                }
            }
        }
        return $optionsResult;
    }

    /**
     * Quite similar to ProfileModel->buildTagOptions
     * @param \ArrayAccess $tags
     * @param Node $filterNode
     * @return array
     */
    protected function buildTags(\ArrayAccess $tags, Node $filterNode)
    {
        $tagsResult = array();
        /* @var Node $tag */
        foreach ($tags as $tag) {
            $labels = $tag->getLabels();
            $relationship = $this->getFilterRelationshipFromNode($tag, $filterNode->getId());
            /* @var Label $label */
            foreach ($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileTag') {
                    $typeName = $this->profileFilterModel->labelToType($label->getName());
                    $tagResult = $tag->getProperty('name');
                    $detail = $relationship->getProperty('detail');
                    if (!is_null($detail)) {
                        $tagResult = array();
                        $tagResult['tag'] = $tag->getProperty('name');
                        if (is_array($detail)) {
                            $tagResult['choices'] = $detail;
                        } else {
                            $tagResult['choice'] = $detail;
                        }
                    }
                    if ($typeName === 'language') {
                        if (is_null($detail)) {
                            $tagResult = array();
                            $tagResult['tag'] = $tag->getProperty('name');
                            $tagResult['choice'] = '';
                        }
                    }
                    $tagsResult[$typeName][] = $tagResult;
                }
            }
        }
        return $tagsResult;
    }

    //TODO: Refactor to GraphManager? Used in ProfileModel too
    //TODO: Can get slow (increments with filter amount), change to cypher specifying id from beginning
    /**
     * @param Node $node
     * @param $sourceId
     * @return Relationship|null
     */
    private function getFilterRelationshipFromNode(Node $node, $sourceId)
    {
        /* @var $relationships Relationship[] */
        $relationships = $node->getRelationships('FILTERS_BY', Relationship::DirectionIn);
        foreach ($relationships as $relationship) {
            if ($relationship->getEndNode()->getId() === $node->getId() &&
                $relationship->getStartNode()->getId() === $sourceId
            ) {
                return $relationship;
            }
        }

        return null;
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
        $qb->match('(filter:FilterUsers)')
            ->where('id(filter) = {id}')
            ->optionalMatch('(filter)-[:FILTERS_BY]->(group:Group)')
            ->returns(
                'filter.compatibility as compatibility,
                        filter.similarity as similarity, 
                        collect(id(group)) as groups,
                        filter.order as order'
            );
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('filter with id ' . $id . ' not found');
        }

        $userFilters = array();

        /** @var Row $row */
        $row = $result->current();

        $userFilters['groups'] = array();
        foreach ($row->offsetGet('groups') as $groupNode) {
            $userFilters['groups'][] = $groupNode;
        }

        if (empty($userFilters['groups'])) {
            unset($userFilters['groups']);
        }

        if ($row->offsetGet('similarity')) {
            $userFilters['similarity'] = $row->offsetGet('similarity');
        }

        if ($row->offsetGet('compatibility')) {
            $userFilters['compatibility'] = $row->offsetGet('compatibility');
        }

        if ($row->offsetGet('order')) {
            $userFilters['order'] = $row->offsetGet('order');
        }

        return $userFilters;
    }

}