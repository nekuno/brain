<?php

namespace Model\Filters;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Location\LocationManager;
use Model\Metadata\MetadataUtilities;
use Model\Neo4j\GraphManager;
use Model\Metadata\UserFilterMetadataManager;
use Model\Neo4j\QueryBuilder;
use Model\Profile\ProfileOptionManager;
use Service\Validator\FilterUsersValidator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FilterUsersManager
{
    /**
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * @var UserFilterMetadataManager
     */
    protected $userFilterMetadataManager;

    protected $profileOptionManager;

    protected $metadataUtilities;

    protected $locationManager;

    /**
     * @var FilterUsersValidator
     */
    protected $validator;

    public function __construct(GraphManager $graphManager, UserFilterMetadataManager $userFilterMetadataManager, ProfileOptionManager $profileOptionManager, LocationManager $locationManager, MetadataUtilities $metadataUtilities, FilterUsersValidator $validator)
    {
        $this->graphManager = $graphManager;
        $this->userFilterMetadataManager = $userFilterMetadataManager;
        $this->profileOptionManager = $profileOptionManager;
        $this->metadataUtilities = $metadataUtilities;
        $this->locationManager = $locationManager;
        $this->validator = $validator;
    }

    public function getFilterUsersByThreadId($id)
    {
        $filterId = $this->getFilterUsersIdByThreadId($id);

        return $this->getFilterUsersById($filterId);
    }

    public function updateFilterUsersByThreadId($id, $filtersArray)
    {
        $filters = $this->buildFiltersUsers($filtersArray);

        $filterId = $this->getFilterUsersIdByThreadId($id);
        $filters->setId($filterId);

        $this->updateFiltersUsers($filters);

        return $filters;
    }

    public function updateFilterUsersByGroupId($id, $filtersArray)
    {
        $filters = $this->buildFiltersUsers($filtersArray);

        $filterId = $this->getFilterUsersIdByGroupId($id);
        $filters->setId($filterId);

        $this->updateFiltersUsers($filters);

        return $filters;
    }

    /**
     * @param $filterId
     * @return FilterUsers
     */
    public function getFilterUsersById($filterId)
    {
        $filtersArray = $this->getFilters($filterId);
        $filter = $this->buildFiltersUsers($filtersArray);
        $filter->setId($filterId);
        return $filter;
    }

    public function validateOnCreate(array $filters, $userId = null)
    {
        $this->validator->validateOnCreate($filters, $userId);
    }

    public function validateOnUpdate(array $filters, $userId = null)
    {
        $this->validator->validateOnUpdate($filters, $userId);
    }

    public function delete(FilterUsers $filters)
    {
        $filterId = $filters->getId();

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:FilterUsers)')
            ->where('id(filter) = {id}')
            ->with('filter')
            ->setParameter('id', (integer)$filterId);

        $qb->detachDelete('filter');

        $result = $qb->getQuery()->getResultSet();

        return $result->count() >= 1;
    }

    /**
     * @param FilterUsers $filters
     * @return bool
     */
    protected function updateFiltersUsers(FilterUsers $filters)
    {
        $filterId = $filters->getId();

//        $this->validateOnUpdate(array('profileFilters' => $profileFilters));

        $metadata = $this->userFilterMetadataManager->getMetadata();

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:FilterUsers)')
            ->where('id(filter) = {id}')
            ->with('filter')
            ->setParameter('id', (integer)$filterId);

        $this->saveGroupFilter($qb, $filters);

        foreach ($metadata as $fieldName => $fieldData) {
            if ($fieldName === 'groups') {
                continue;
            }
            $value = $filters->get($fieldName);
            switch ($fieldType = $metadata[$fieldName]['type']) {
                case 'text':
                case 'textarea':
                    $qb->remove("filter.$fieldName");

                    if ($value) {
                        $qb->set("filter.$fieldName = '$value'");
                    }
                    $qb->with('filter');
                    break;
                //TODO: Refactor this and integer_range into saving and loading arrays to the Node
                case 'birthday_range':

                    $qb->remove("filter.age_min", "filter.age_max");
                    if ($value) {
                        if (isset($value['min']) && null !== $value['min']) {
                            $qb->set('filter.age_min = ' . $value['min']);
                        }
                        if (isset($value['max']) && null !== $value['max']) {
                            $qb->set('filter.age_max = ' . $value['max']);
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'integer_range':

                    $fieldNameMin = $fieldName . '_min';
                    $fieldNameMax = $fieldName . '_max';
                    $qb->remove("filter.$fieldNameMin", "filter.$fieldNameMax");

                    if ($value) {
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

                    if ($value) {
                        $qb->setParameter('distance', (int)$value['distance']);
                        $qb->setParameter('latitude', (float)$value['location']['latitude']);
                        $qb->setParameter('longitude', (float)$value['location']['longitude']);
                        $qb->setParameter('address', $value['location']['address']);
                        $qb->setParameter('locality', $value['location']['locality']);
                        $qb->setParameter('country', $value['location']['country']);
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

                    if ($value) {
                        $qb->set("filter.$fieldName = true");
                    }
                    $qb->with('filter');
                    break;
                case 'choice':
                    $profileLabelName = $this->metadataUtilities->typeToLabel($fieldName);
                    $qb->optionalMatch("(filter)-[old_po_rel:FILTERS_BY]->(:$profileLabelName)")
                        ->delete("old_po_rel");

                    if ($value) {
                        $qb->merge(" (option$fieldName:$profileLabelName{id:'$value'})");
                        $qb->merge(" (filter)-[:FILTERS_BY]->(option$fieldName)");
                    }
                    $qb->with('filter');
                    break;
                case 'double_multiple_choices':
                    $profileLabelName = $this->metadataUtilities->typeToLabel($fieldName);
                    $qb->optionalMatch("(filter)-[old_po_rel:FILTERS_BY]->(:$profileLabelName)")
                        ->delete("old_po_rel");
                    if ($value) {
                        $details = isset($value['details']) ? $value['details'] : array();

                        if ($value && isset($value['choices'])) {
                            foreach ($value['choices'] as $index => $choice) {
                                $qb->merge(" (option$fieldName$index:$profileLabelName{id:'$choice'})");
                                $qb->merge(" (filter)-[po_rel$fieldName$index:FILTERS_BY]->(option$fieldName$index)")
                                    ->set(" po_rel$fieldName$index.details = {details$fieldName$index}");
                                $qb->setParameter("details$fieldName$index", $details);
                            }
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'choice_and_multiple_choices':
                    $profileLabelName = $this->metadataUtilities->typeToLabel($fieldName);
                    $qb->optionalMatch("(filter)-[old_po_rel:FILTERS_BY]->(:$profileLabelName)")
                        ->delete("old_po_rel");
                    if ($value && isset($value['choice'])) {
                        $choice = $value['choice'];
                        $details = isset($value['details']) ? $value['details'] : array();

                        $qb->merge(" (option$fieldName:$profileLabelName{id:'$choice'})");
                        $qb->merge(" (filter)-[po_rel$fieldName:FILTERS_BY]->(option$fieldName)")
                            ->set(" po_rel$fieldName.details = {details$fieldName}");
                        $qb->setParameter("details$fieldName", $details);
                    }
                    $qb->with('filter');
                    break;
                case 'multiple_choices':
                    $profileLabelName = $this->metadataUtilities->typeToLabel($fieldName);
                    $qb->optionalMatch("(filter)-[old_po_rel:FILTERS_BY]->(:$profileLabelName)")
                        ->delete("old_po_rel");

                    if ($value) {
                        $counter = 0;
                        foreach ($value as $singleValue) {
                            $qb->merge(" (option$fieldName$counter:$profileLabelName{id:'$singleValue'})");
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
                    if ($value) {
                        foreach ($value as $tag) {
                            $qb->merge("(tag$fieldName$tag:$tagLabelName:ProfileTag)<-[:TEXT_OF]-(:TextLanguage{text:'$tag'})");
                            $qb->merge("(filter)-[:FILTERS_BY]->(tag$fieldName$tag)");
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'tags_and_choice':
                    $tagLabelName = ucfirst($fieldName);
                    $qb->optionalMatch("(filter)-[old_tag_rel:FILTERS_BY]->(:$tagLabelName)")
                        ->delete("old_tag_rel");

                    if ($value) {
                        foreach ($value as $singleValue) {
                            $tag = $singleValue['tag'];
                            $choice = isset($singleValue['choice']) ? $singleValue['choice'] : '';

                            $qb->merge("(tag$fieldName$tag:$tagLabelName:ProfileTag)<-[:TEXT_OF]-(:TextLanguage{text:'$tag'})");
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

                    if ($value) {
                        foreach ($value as $singleValue) {
                            $tag = $singleValue['tag'];
                            $choices = isset($singleValue['choices']) ? $singleValue['choices'] : '';
                            $qb->merge("(tag$fieldName$tag:$tagLabelName:ProfileTag)<-[:TEXT_OF]-(:TextLanguage{text:'$tag'})");
                            $qb->merge("(filter)-[tag_rel$fieldName$tag:FILTERS_BY]->(tag$fieldName$tag)")
                                ->set("tag_rel$fieldName$tag.detail = {detail$fieldName$tag}");
                            $qb->setParameter("detail$fieldName$tag", $choices);
                        }
                    }
                    $qb->with('filter');
                    break;
                case 'order':
                    $qb->remove("filter.$fieldName");

                    if ($value) {
                        $qb->set('filter.' . $fieldName . ' = "' . $value . '"');
                    }
                    $qb->with('filter');
                    break;
                case 'integer':
                    $qb->remove("filter.$fieldName");

                    if ($value) {
                        $qb->set('filter.' . $fieldName . ' = ' . $value);
                    }
                    $qb->with('filter');
                    break;
                default:
                    break;
            }
        }

        $qb->returns('filter');
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return null;
        }

        return $result->current()->offsetGet('filter');
    }

    /**
     * @param array $filtersArray
     * @return FilterUsers
     */
    protected function buildFiltersUsers(array $filtersArray)
    {
        $metadata = $this->userFilterMetadataManager->getMetadata();
        $filters = new FilterUsers($metadata);
        foreach ($filtersArray['userFilters'] as $field => $value) {
            $filters->set($field, $value);
        }
        return $filters;
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

    private function saveGroupFilter(QueryBuilder $qb, FilterUsers $filters)
    {
        $qb->optionalMatch('(filter)-[old_rel_group:FILTERS_BY]->(:Group)')
            ->delete('old_rel_group')
            ->with('filter');

        $value = $filters->get('groups');
        if ($value) {
            foreach ($value as $group) {
                $qb->match("(group$group:Group)")
                    ->where("id(group$group) = $group")
                    ->merge("(filter)-[:FILTERS_BY]->(group$group)")
                    ->with('filter');
            }
        }
    }

    /**
     * Creates array ready to use as profileFilter from neo4j
     * @param $filterId
     * @return array ready to use in recommendation
     */
    private function getFilters($filterId)
    {
        //TODO: Refactor this into metadata
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:FilterUsers)')
            ->where('id(filter) = {id}')
            ->optionalMatch('(filter)-[:FILTERS_BY]->(po:ProfileOption)')
            ->with('filter', 'collect(distinct po) as options')
            ->optionalMatch('(filter)-[:FILTERS_BY]->(pt:ProfileTag)')
            ->with('filter', 'options', 'collect(distinct pt) as tags')
            ->optionalMatch('(filter)-[loc_rel:FILTERS_BY]->(loc:Location)')
            ->with('filter', 'options', 'tags', 'loc', 'loc_rel')
            ->optionalMatch('(filter)-[:FILTERS_BY]->(group:Group)')
            ->returns('filter, options, tags, loc, loc_rel', 'collect(id(group)) AS groups');
        $qb->setParameter('id', (integer)$filterId);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('filter with id ' . $filterId . ' not found');
        }

        /** @var Row $row */
        $row = $result->current();
        /** @var Node $filterNode */
        $filterNode = $row->offsetGet('filter');
        $options = $row->offsetGet('options');
        $tags = $row->offsetGet('tags');

        $filters = $this->buildProfileOptions($options, $filterNode);
        $filters += $this->buildTags($tags, $filterNode);

        if ($filterNode->getProperty('age_min') || $filterNode->getProperty('age_max')) {
            $filters += array(
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
            $filters['height'] = $height;
        }

        if ($filterNode->getProperty('similarity')) {
            $filters['similarity'] = $filterNode->getProperty('similarity');
        }

        if ($filterNode->getProperty('compatibility')) {
            $filters['compatibility'] = $filterNode->getProperty('compatibility');
        }

        if ($filterNode->getProperty('order')) {
            $filters['order'] = $filterNode->getProperty('order');
        }

        $filters['groups'] = array();
        foreach ($row->offsetGet('groups') as $groupNode) {
            $filters['groups'][] = $groupNode;
        }

        if (empty($filters['groups'])) {
            unset($filters['groups']);
        }

        /** @var Node $location */
        $location = $row->offsetGet('loc');
        if ($location instanceof Node) {

            /** @var Relationship $locationRelationship */
            $locationRelationship = $row->offsetGet('loc_rel');
            $filters += array(
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

        $filters = array_filter($filters);

        return array('userFilters' => $filters);
    }

    /**
     * TODO: Check if merge with ProfileOptionManager->buildOptions or add ->buildFilterOptions(options, node)
     * @param \ArrayAccess $options
     * @param Node $filterNode
     * @return array
     */
    private function buildProfileOptions(\ArrayAccess $options, Node $filterNode)
    {
        $filterMetadata = $this->userFilterMetadataManager->getMetadata();
        $optionsResult = array();
        /* @var Node $option */
        foreach ($options as $option) {
            $labels = $option->getLabels();
            $relationship = $this->getFilterRelationshipFromNode($option, $filterNode->getId());
            /* @var Label $label */
            foreach ($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileOption') {
                    $typeName = $this->metadataUtilities->labelToType($label->getName());
                    $metadataValues = isset($filterMetadata[$typeName]) ? $filterMetadata[$typeName] : array();

                    switch ($metadataValues['type']) {
                        case 'double_multiple_choices':
                            $optionsResult[$typeName] = isset($optionsResult[$typeName]) && is_array($optionsResult[$typeName]) ?
                                $optionsResult[$typeName] : array('choices' => array(), 'details' => array());
                            $optionsResult[$typeName]['choices'][] = $option->getProperty('id');
                            $optionsResult[$typeName]['details'] = $relationship->getProperty('details');
                            break;
                        case 'choice_and_multiple_choices':
                            $optionsResult[$typeName] = array('choice' => $option->getProperty('id'), 'details' => $relationship->getProperty('details'));
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
                    $typeName = $this->metadataUtilities->labelToType($label->getName());
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

}