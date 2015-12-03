<?php

namespace Model\User;

use Model\Neo4j\GraphManager;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Label;
use Model\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProfileModel
{
    const MAX_TAGS_AND_CHOICE_LENGTH = 15;
    protected $client;
    protected $metadata;
    protected $defaultLocale;

    public function __construct(GraphManager $gm, array $metadata, $defaultLocale)
    {

        $this->gm = $gm;
        $this->metadata = $metadata;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Returns the metadata for editing the profile
     * @param null $locale Locale of the metadata
     * @param bool $filter Filter non public attributes
     * @return array
     */
    public function getMetadata($locale = null, $filter = true)
    {
        $locale = $this->getLocale($locale);
        $choiceOptions = $this->getChoiceOptions($locale);

        $publicMetadata = array();
        foreach ($this->metadata as $name => $values) {
            $publicField = $values;
            $publicField['label'] = $values['label'][$locale];

            if ($values['type'] === 'choice') {
                $publicField['choices'] = array();
                if (isset($choiceOptions[$name])) {
                    $publicField['choices'] = $choiceOptions[$name];
                }
            } elseif ($values['type'] === 'double_choice') {
                $publicField['choices'] = array();
                if (isset($choiceOptions[$name])) {
                    $publicField['choices'] = $choiceOptions[$name];
                    if (isset($values['doubleChoices'])) {
                        foreach ($values['doubleChoices'] as $choice => $doubleChoices) {
                            foreach ($doubleChoices as $doubleChoice => $doubleChoiceValues) {
                                $publicField['doubleChoices'][$choice][$doubleChoice] = $doubleChoiceValues[$locale];
                            }
                        }
                    }
                }
            } elseif ($values['type'] === 'multiple_choices') {
                $publicField['choices'] = array();
                if (isset($choiceOptions[$name])) {
                    $publicField['choices'] = $choiceOptions[$name];
                }
                if (isset($values['max_choices'])) {
                    $publicField['max_choices'] = $values['max_choices'];
                }
            } elseif ($values['type'] === 'tags_and_choice') {
                $publicField['choices'] = array();
                if (isset($values['choices'])) {
                    foreach ($values['choices'] as $choice => $description) {
                        $publicField['choices'][$choice] = $description[$locale];
                    }
                }
                $publicField['top'] = $this->getTopProfileTags($name);
            } elseif ($values['type'] === 'tags') {
                $publicField['top'] = $this->getTopProfileTags($name);
            }

            $publicMetadata[$name] = $publicField;
        }

        if ($filter) {
            foreach ($publicMetadata as &$item) {
                if (isset($item['labelFilter'])) {
                    unset($item['labelFilter']);
                }
                if (isset($item['filterable'])) {
                    unset($item['filterable']);
                }
            }
        }

        return $publicMetadata;
    }

    /**
     * Returns the metadata for creating search filters
     * @param null $locale
     * @return array
     */
    public function getFilters($locale = null)
    {

        $locale = $this->getLocale($locale);
        $metadata = $this->getMetadata($locale, false);
        $labels = array();
        foreach ($metadata as $key => &$item) {
            if (isset($item['labelFilter'])) {
                $item['label'] = $item['labelFilter'][$locale];
                unset($item['labelFilter']);
            }
            if (isset($item['filterable']) && $item['filterable'] === false) {
                unset($metadata[$key]);
            } else {
                $labels[] = $item['label'];
            }
        }

        if (!empty($labels)) {
            array_multisort($labels, SORT_ASC, $metadata);
        }

        return $metadata;
    }

    /**
     * @param int $id
     * @param mixed $locale
     * @return array
     * @throws NotFoundHttpException
     */
    public function getById($id, $locale = null)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[:PROFILE_OF]-(profile:Profile)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $id)
            ->optionalMatch('(profile)<-[:OPTION_OF]-(option:ProfileOption)')
            ->optionalMatch('(profile)-[:TAGGED]-(tag:ProfileTag)')
            ->optionalMatch('(profile)-[:LOCATION]->(location:Location)')
            ->returns('profile, location, collect(distinct option) AS options, collect(distinct tag) as tags')
            ->limit(1);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Profile not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row, $locale);
    }

    /**
     * @param $id
     * @param array $data
     * @return array
     * @throws NotFoundHttpException|MethodNotAllowedHttpException
     */
    public function create($id, array $data)
    {
        $this->validate($data);

        list($userNode, $profileNode) = $this->getUserAndProfileNodesById($id);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if ($profileNode instanceof Node) {
            throw new MethodNotAllowedHttpException(array('PUT'), 'Profile already exists');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', (int)$id)
            ->merge('(profile:Profile)-[po:PROFILE_OF]->(user)');

        $qb->getQuery()->getResultSet();

        $this->saveProfileData($id, $data);

        return $this->getById($id);
    }

    /**
     * @param integer $id
     * @param array $data
     * @return array
     * @throws ValidationException|NotFoundHttpException
     */
    public function update($id, array $data)
    {
        $this->validate($data);

        list($userNode, $profileNode) = $this->getUserAndProfileNodesById($id);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if (!($profileNode instanceof Node)) {
            throw new NotFoundHttpException('Profile not found');
        }

        $this->saveProfileData($id, $data);

        return $this->getById($id);
    }

    /**
     * @param $id
     */
    public function remove($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[:PROFILE_OF]-(profile:Profile)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $id)
            ->optionalMatch('(profile)-[r]-()')
            ->delete('r, profile');

        $query = $qb->getQuery();

        $query->getResultSet();
    }

    /**
     * @param array $data
     * @throws ValidationException
     */
    public function validate(array $data)
    {
        $errors = array();
        $metadata = $this->getMetadata();

        foreach ($metadata as $fieldName => $fieldData) {

            $fieldErrors = array();

            if (isset($data[$fieldName])) {

                $fieldValue = $data[$fieldName];

                if (isset($fieldData['type'])) {
                    switch ($fieldData['type']) {
                        case 'text':
                        case 'textarea':
                            if (isset($fieldData['min'])) {
                                if (strlen($fieldValue) < $fieldData['min']) {
                                    $fieldErrors[] = 'Must have ' . $fieldData['min'] . ' characters min.';
                                }
                            }
                            if (isset($fieldData['max'])) {
                                if (strlen($fieldValue) > $fieldData['max']) {
                                    $fieldErrors[] = 'Must have ' . $fieldData['max'] . ' characters max.';
                                }
                            }
                            break;

                        case 'integer':
                            if (!is_integer($fieldValue)) {
                                $fieldErrors[] = 'Must be an integer';
                            }
                            if (isset($fieldData['min'])) {
                                if (!empty($fieldValue) && $fieldValue < $fieldData['min']) {
                                    $fieldErrors[] = 'Must be greater than ' . $fieldData['min'];
                                }
                            }
                            if (isset($fieldData['max'])) {
                                if ($fieldValue > $fieldData['max']) {
                                    $fieldErrors[] = 'Must be less than ' . $fieldData['max'];
                                }
                            }
                            break;

                        case 'date':
                            $date = \DateTime::createFromFormat('Y-m-d', $fieldValue);
                            if (!($date && $date->format('Y-m-d') == $fieldValue)) {
                                $fieldErrors[] = 'Invalid date format, valid format is "Y-m-d".';
                            }
                            break;

                        case 'birthday':
                            $date = \DateTime::createFromFormat('Y-m-d', $fieldValue);
                            $now = new \DateTime();
                            if (!($date && $date->format('Y-m-d') == $fieldValue)) {
                                $fieldErrors[] = 'Invalid date format, valid format is "YYYY-MM-DD".';
                            } elseif ($now < $date) {
                                $fieldErrors[] = 'Invalid birthday date, can not be in the future.';
                            } elseif ($now->modify('-14 year') < $date) {
                                $fieldErrors[] = 'Invalid birthday date, you must be older than 14 years.';
                            }
                            break;

                        case 'boolean':
                            if ($fieldValue !== true && $fieldValue !== false) {
                                $fieldErrors[] = 'Must be a boolean.';
                            }
                            break;

                        case 'choice':
                            $choices = $fieldData['choices'];
                            if (!in_array($fieldValue, array_keys($choices))) {
                                $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $fieldValue, implode("', '", array_keys($choices)));
                            }
                            break;

                        case 'double_choice':
                            $choices = $fieldData['choices'] + array('' => '');
                            if (!in_array($fieldValue['choice'], array_keys($choices))) {
                                $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $fieldValue['choice'], implode("', '", array_keys($choices)));
                            }
                            $doubleChoices = $fieldData['doubleChoices'] + array('' => '');
                            if (!isset($doubleChoices[$fieldValue['choice']]) || $fieldValue['detail'] && !isset($doubleChoices[$fieldValue['choice']][$fieldValue['detail']])) {
                                $fieldErrors[] = sprintf('Option choice and detail must be set in "%s"', $fieldValue['choice']);
                            } elseif ($fieldValue['detail'] && !in_array($fieldValue['detail'], array_keys($doubleChoices[$fieldValue['choice']]))) {
                                $fieldErrors[] = sprintf('Detail with value "%s" is not valid, possible values are "%s"', $fieldValue['detail'], implode("', '", array_keys($doubleChoices)));
                            }
                            break;
                        case 'tags_and_choice':
                            $choices = $fieldData['choices'];
                            if (count($fieldValue) > self::MAX_TAGS_AND_CHOICE_LENGTH) {
                                $fieldErrors[] = sprintf('Tags and choice length "%s" is too long. "%s" is the maximum', count($fieldValue), self::MAX_TAGS_AND_CHOICE_LENGTH);
                            }
                            foreach ($fieldValue as $tagAndChoice) {
                                if (!isset($tagAndChoice['tag']) || !array_key_exists('choice', $tagAndChoice)) {
                                    $fieldErrors[] = sprintf('Tag and choice must be defined for tags and choice type');
                                }
                                if (isset($tagAndChoice['choice']) && !in_array($tagAndChoice['choice'], array_keys($choices))) {
                                    $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $tagAndChoice['choice'], implode("', '", array_keys($choices)));
                                }
                            }
                            break;
                        case 'multiple_choices':
                            $choices = $fieldData['choices'];
                            if (count($fieldValue) > $fieldData['max_choices']) {
                                $fieldErrors[] = sprintf('Option length "%s" is too long. "%s" is the maximum', count($fieldValue), $fieldData['max_choices']);
                            }
                            foreach($fieldValue as $value) {
                                if (!in_array($value, array_keys($choices))) {
                                    $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $value, implode("', '", array_keys($choices)));
                                }
                            }
                            break;
                        case 'location':
                            if (!is_array($fieldValue)) {
                                $fieldErrors[] = sprintf('The value "%s" is not valid, it should be an array with "latitude" and "longitude" keys', $fieldValue);
                            } else {
                                if (!isset($fieldValue['address']) || !$fieldValue['address'] || !is_string($fieldValue['address'])) {
                                    $fieldErrors[] = 'Address required';
                                } else {
                                    if (!isset($fieldValue['latitude']) || !preg_match("/^-?([1-8]?[0-9]|[1-9]0)\.{1}\d+$/", $fieldValue['latitude'])) {
                                        $fieldErrors[] = 'Latitude not valid';
                                    } elseif (!is_float($fieldValue['latitude'])) {
                                        $fieldErrors[] = 'Latitude must be float';
                                    }
                                    if (!isset($fieldValue['longitude']) || !preg_match("/^-?([1]?[0-7][1-9]|[1]?[1-8][0]|[1-9]?[0-9])\.{1}\d+$/", $fieldValue['longitude'])) {
                                        $fieldErrors[] = 'Longitude not valid';
                                    } elseif (!is_float($fieldValue['longitude'])) {
                                        $fieldErrors[] = 'Longitude must be float';
                                    }
                                    if (!isset($fieldValue['locality']) || !$fieldValue['locality'] || !is_string($fieldValue['locality'])) {
                                        $fieldErrors[] = 'Locality required';
                                    }
                                    if (!isset($fieldValue['country']) || !$fieldValue['country'] || !is_string($fieldValue['country'])) {
                                        $fieldErrors[] = 'Country required';
                                    }
                                }
                            }
                            break;
                    }
                }
            } else {

                if ($fieldName === 'orientation' && isset($data['orientationRequired']) && $data['orientationRequired'] === false) {
                    continue;
                }

                if (isset($fieldData['required']) && $fieldData['required'] === true) {
                    $fieldErrors[] = 'It\'s required.';
                }
            }

            if (count($fieldErrors) > 0) {
                $errors[$fieldName] = $fieldErrors;
            }
        }

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    protected function build(Row $row, $locale = null)
    {
        /* @var $node Node */
        $node = $row->offsetGet('profile');
        $profile = $node->getProperties();

        /* @var $location Node */
        $location = $row->offsetGet('location');
        if ($location && count($location->getProperties()) > 0) {
            $profile['location'] = $location->getProperties();
        }

        $profile += $this->buildOptions($row);
        $profile += $this->buildTags($row, $locale);

        return $profile;
    }

    protected function buildOptions(Row $row)
    {
        $options = $row->offsetGet('options');
        /* @var Node $profile */
        $profile = $row->offsetGet('profile');

        $optionsResult = array();
        /* @var Node $option */
        foreach ($options as $option) {
            $labels = $option->getLabels();
            /* @var Relationship $relationship */
            $relationships = $option->getRelationships('OPTION_OF', Relationship::DirectionOut);
            foreach ($relationships as $relationship) {
                if ($relationship->getStartNode()->getId() === $option->getId() &&
                    $relationship->getEndNode()->getId() === $profile->getId()
                ) {
                    break;
                }
            }
            /* @var Label $label */
            foreach ($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileOption') {
                    $typeName = $this->labelToType($label->getName());
                    if (isset($optionsResult[$typeName]) && $optionsResult[$typeName]) {
                        if (is_array($optionsResult[$typeName])) {
                            $optionsResult[$typeName] += array($option->getProperty('id'));
                        } else {
                            $optionsResult[$typeName] = array($optionsResult[$typeName], $option->getProperty('id'));
                        }
                    } else {
                        $optionsResult[$typeName] = $option->getProperty('id');
                        $detail = $relationship->getProperty('detail');
                        if (!is_null($detail)) {
                            $optionsResult[$typeName] = array();
                            $optionsResult[$typeName]['choice'] = $option->getProperty('id');
                            $optionsResult[$typeName]['detail'] = $detail;
                        }
                    }
                }
            }
        }

        return $optionsResult;
    }

    protected function buildTags(Row $row, $locale = null)
    {
        $locale = $this->getLocale($locale);
        $tags = $row->offsetGet('tags');
        /* @var Node $profile */
        $profile = $row->offsetGet('profile');

        $tagsResult = array();
        /* @var Node $tag */
        foreach ($tags as $tag) {
            $labels = $tag->getLabels();
            /* @var Relationship $relationship */
            $relationships = $tag->getRelationships('TAGGED', Relationship::DirectionOut);
            foreach ($relationships as $relationship) {
                if ($relationship->getStartNode()->getId() === $tag->getId() &&
                    $relationship->getEndNode()->getId() === $profile->getId()
                ) {
                    break;
                }
            }
            /* @var Label $label */
            foreach ($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileTag') {
                    $typeName = $this->labelToType($label->getName());
                    $tagResult = $tag->getProperty('name');
                    $detail = $relationship->getProperty('detail');
                    if (!is_null($detail)) {
                        $tagResult = array();
                        $tagResult['tag'] = $tag->getProperty('name');
                        $tagResult['detail'] = $detail;
                    }
                    if ($typeName === 'language') {
                        if (is_null($detail)) {
                            $tagResult = array();
                            $tagResult['tag'] = $tag->getProperty('name');
                            $tagResult['detail'] = '';
                        }
                        $tagResult['tag'] = $this->translateLanguageToLocale($tagResult['tag'], $locale);
                    }
                    $tagsResult[$typeName][] = $tagResult;
                }
            }
        }

        return $tagsResult;
    }

    protected function getChoiceOptions($locale)
    {
        $translationField = 'name_' . $locale;

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(option:ProfileOption)')
            ->returns("head(filter(x IN labels(option) WHERE x <> 'ProfileOption')) AS labelName, option.id AS id, option." . $translationField . " AS name")
            ->orderBy('labelName');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $choiceOptions = array();
        /** @var Row $row */
        foreach ($result as $row) {
            $typeName = $this->labelToType($row->offsetGet('labelName'));
            $optionId = $row->offsetGet('id');
            $optionName = $row->offsetGet('name');

            $choiceOptions[$typeName][$optionId] = $optionName;
        }

        return $choiceOptions;
    }

    protected function getUserAndProfileNodesById($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)')
            ->where('user.qnoow_id = { id }')
            ->optionalMatch('(user)<-[:PROFILE_OF]-(profile:Profile)')
            ->setParameter('id', $id)
            ->returns('user', 'profile')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if (count($result) < 1) {
            return array(null, null);
        }

        /** @var Row $row */
        $row = $result->current();
        $userNode = $row->offsetGet('user');
        $profileNode = $row->offsetGet('profile');

        return array($userNode, $profileNode);
    }

    protected function saveProfileData($id, array $data)
    {
        $metadata = $this->getMetadata();
        $options = $this->getProfileNodeOptions($id);
        $tags = $this->getProfileNodeTags($id);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$id)
            ->with('profile');

        foreach ($data as $fieldName => $fieldValue) {
            if (isset($metadata[$fieldName])) {

                $fieldType = $metadata[$fieldName]['type'];
                $editable = isset($metadata[$fieldName]['editable']) ? $metadata[$fieldName]['editable'] === true : true;

                if (!$editable) {
                    continue;
                }

                switch ($fieldType) {
                    case 'text':
                    case 'textarea':
                    case 'date':
                    case 'boolean':
                    case 'integer':
                        $qb->set('profile.' . $fieldName . ' = { ' . $fieldName . ' }')
                            ->setParameter($fieldName, $fieldValue)
                            ->with('profile');
                        break;
                    case 'birthday':
                        $zodiacSign = $this->getZodiacSignFromDate($fieldValue);
                        if (isset($options['zodiacSign'])) {
                            $qb->match('(profile)<-[zodiacSignRel:OPTION_OF]-(zs:ZodiacSign)')
                                ->delete('zodiacSignRel')
                                ->with('profile');
                        }
                        if (!is_null($zodiacSign)) {
                            $qb->match('(newZs:ZodiacSign {id: { zodiacSign }})')
                                ->merge('(profile)<-[:OPTION_OF]-(newZs)')
                                ->setParameter('zodiacSign', $zodiacSign)
                                ->with('profile');
                        }

                        $qb->set('profile.' . $fieldName . ' = { birthday }')
                            ->setParameter('birthday', $fieldValue)
                            ->with('profile');
                        break;
                    case 'location':
                        $qb->optionalMatch('(profile)-[rLocation:LOCATION]->(oldLocation:Location)')
                            ->delete('rLocation', 'oldLocation')
                            ->with('profile');

                        $qb->create('(location:Location {latitude: { latitude }, longitude: { longitude }, address: { address }, locality: { locality }, country: { country }})')
                            ->createUnique('(profile)-[:LOCATION]->(location)')
                            ->setParameter('latitude', $fieldValue['latitude'])
                            ->setParameter('longitude', $fieldValue['longitude'])
                            ->setParameter('address', $fieldValue['address'])
                            ->setParameter('locality', $fieldValue['locality'])
                            ->setParameter('country', $fieldValue['country'])
                            ->with('profile');
                        break;
                    case 'choice':
                        if (isset($options[$fieldName])) {
                            $qb->optionalMatch('(profile)<-[optionRel:OPTION_OF]-(:' . $this->typeToLabel($fieldName) . ')')
                                ->delete('optionRel')
                                ->with('profile');
                        }
                        if (!is_null($fieldValue)) {
                            $qb->match('(option:' . $this->typeToLabel($fieldName) . ' {id: { ' . $fieldName . ' }})')
                                ->merge('(profile)<-[:OPTION_OF]-(option)')
                                ->setParameter($fieldName, $fieldValue)
                                ->with('profile');
                        }
                        break;
                    case 'double_choice':
                        if (isset($options[$fieldName])) {
                            $qb->optionalMatch('(profile)<-[doubleChoiceOptionRel:OPTION_OF]-(:' . $this->typeToLabel($fieldName) . ')')
                                ->delete('doubleChoiceOptionRel')
                                ->with('profile');
                        }
                        if (isset($fieldValue['choice'])) {
                            $detail = !is_null($fieldValue['detail']) ? $fieldValue['detail'] : '';
                            $qb->match('(option:' . $this->typeToLabel($fieldName) . ' {id: { ' . $fieldName . ' }})')
                                ->merge('(profile)<-[:OPTION_OF {detail: {' . $fieldName . '_detail}}]-(option)')
                                ->setParameter($fieldName, $fieldValue['choice'])
                                ->setParameter($fieldName . '_detail', $detail)
                                ->with('profile');
                        }

                        break;
                    case 'tags_and_choice':
                        if (is_array($fieldValue)) {
                            $qbTagsAndChoice = $this->gm->createQueryBuilder();
                            $qbTagsAndChoice->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
                                ->where('u.qnoow_id = { id }')
                                ->setParameter('id', (int)$id)
                                ->with('profile');

                            $qbTagsAndChoice->optionalMatch('(profile)<-[tagsAndChoiceOptionRel:TAGGED]-(:' . $this->typeToLabel($fieldName) . ')')
                                ->delete('tagsAndChoiceOptionRel');

                            $savedTags = array();
                            foreach ($fieldValue as $index => $value) {
                                $tagValue = $fieldName === 'language' ?
                                    $this->translateTypicalLanguage($this->formatLanguage($value['tag'])) :
                                    $value['tag'];
                                if (in_array($tagValue, $savedTags)) {
                                    continue;
                                }
                                $choice = !is_null($value['choice']) ? $value['choice'] : '';
                                $tagLabel = 'tag_' . $index;
                                $tagParameter = $fieldName . '_' . $index;
                                $choiceParameter = $fieldName . '_choice_' . $index;

                                $qbTagsAndChoice->with('profile')
                                    ->merge('(' . $tagLabel . ':ProfileTag:' . $this->typeToLabel($fieldName) . ' {name: { ' . $tagParameter . ' }})')
                                    ->merge('(profile)<-[:TAGGED {detail: {' . $choiceParameter . '}}]-(' . $tagLabel . ')')
                                    ->setParameter($tagParameter, $tagValue)
                                    ->setParameter($choiceParameter, $choice);
                                $savedTags[] = $tagValue;
                            }
                            $query = $qbTagsAndChoice->getQuery();
                            $query->getResultSet();
                        }

                        break;
                    case 'multiple_choices':
                        if (isset($options[$fieldName])) {
                            $qb->optionalMatch('(profile)<-[optionRel:OPTION_OF]-(:' . $this->typeToLabel($fieldName) . ')')
                                ->delete('optionRel')
                                ->with('profile');
                        }
                        if (is_array($fieldValue)) {
                            foreach ($fieldValue as $value) {
                                $qb->match('(option:' . $this->typeToLabel($fieldName) . ' {id: { ' . $value . ' }})')
                                    ->merge('(profile)<-[:OPTION_OF]-(option)')
                                    ->setParameter($value, $value)
                                    ->with('profile');
                            }
                        }
                        break;
                    case 'tags':
                        if (isset($tags[$fieldName])) {
                            foreach ($tags[$fieldName] as $tag) {
                                $qb->optionalMatch('(profile)<-[tagRel:TAGGED]-(tag:' . $this->typeToLabel($fieldName) . ' {name: "' . $tag . '" })')
                                    ->delete('tagRel')
                                    ->with('profile');
                            }
                        }
                        if (is_array($fieldValue) && !empty($fieldValue)) {
                            foreach ($fieldValue as $tag) {
                                $qb->merge('(tag:ProfileTag:' . $this->typeToLabel($fieldName) . ' {name: "' . $tag . '" })')
                                    ->merge('(profile)<-[:TAGGED]-(tag)')
                                    ->with('profile');
                            }
                        }

                        break;
                }
            }
        }

        $qb->optionalMatch('(profile)<-[:OPTION_OF]-(option:ProfileOption)')
            ->optionalMatch('(profile)<-[:TAGGED]-(tag:ProfileTag)')
            ->returns('profile', 'collect(distinct option) AS options', 'collect(distinct tag) AS tags')
            ->limit(1);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $this->build($result->current());
    }

    protected function getProfileNodeOptions($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(option:ProfileOption)-[:OPTION_OF]->(profile:Profile)-[:PROFILE_OF]->(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $id)
            ->returns('profile, collect(distinct option) AS options');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $options = array();
        foreach ($result as $row) {
            $options += $this->buildOptions($row);
        }

        return $options;
    }

    protected function getProfileNodeTags($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(tag:ProfileTag)-[:TAGGED]-(profile:Profile)-[:PROFILE_OF]->(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $id)
            ->returns('profile', 'collect(distinct tag) as tags');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $tags = array();
        foreach ($result as $row) {
            $tags += $this->buildTags($row);
        }

        return $tags;
    }

    protected function getTopProfileTags($tagType)
    {

        $tagLabelName = $this->typeToLabel($tagType);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(tag:' . $tagLabelName . ')-[tagged:TAGGED]-(profile:Profile)')
            ->returns('tag.name AS tag, count(*) as count')
            ->limit(5);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $tags = array();
        foreach ($result as $row) {
            /* @var $row Row */
            $tags[] = $row->offsetGet('tag');
        }

        return $tags;
    }

    /*
     * Please don't believe in this crap
     */
    protected function getZodiacSignFromDate($date)
    {

        $sign = null;
        $birthday = \DateTime::createFromFormat('Y-m-d', $date);

        $zodiac[356] = 'capricorn';
        $zodiac[326] = 'sagittarius';
        $zodiac[296] = 'scorpio';
        $zodiac[266] = 'libra';
        $zodiac[235] = 'virgo';
        $zodiac[203] = 'leo';
        $zodiac[172] = 'cancer';
        $zodiac[140] = 'gemini';
        $zodiac[111] = 'taurus';
        $zodiac[78] = 'aries';
        $zodiac[51] = 'pisces';
        $zodiac[20] = 'aquarius';
        $zodiac[0] = 'capricorn';

        if (!$date) {
            return $sign;
        }

        $dayOfTheYear = $birthday->format('z');
        $isLeapYear = $birthday->format('L');
        if ($isLeapYear && ($dayOfTheYear > 59)) {
            $dayOfTheYear = $dayOfTheYear - 1;
        }

        foreach ($zodiac as $day => $sign) {
            if ($dayOfTheYear > $day) {
                break;
            }
        }

        return $sign;
    }

    protected function getLocale($locale)
    {

        if (!$locale || !in_array($locale, array('en', 'es'))) {
            $locale = $this->defaultLocale;
        }

        return $locale;
    }

    protected function labelToType($labelName)
    {

        return lcfirst($labelName);
    }

    protected function typeToLabel($typeName)
    {
        return ucfirst($typeName);
    }

    protected function formatLanguage($typeName)
    {
        $firstCharacter = mb_strtoupper(mb_substr($typeName, 0, 1, 'UTF-8'), 'UTF-8');
        $restString = mb_strtolower(mb_substr($typeName, 1, null, 'UTF-8'), 'UTF-8');

        return $firstCharacter . $restString;
    }

    protected function translateTypicalLanguage($language)
    {
        switch ($language) {
            case 'Español':
                return 'Spanish';
            case 'Castellano':
                return 'Spanish';
            case 'Inglés':
                return 'English';
            case 'Ingles':
                return 'English';
            case 'Francés':
                return 'French';
            case 'Frances':
                return 'French';
            case 'Alemán':
                return 'German';
            case 'Aleman':
                return 'German';
            case 'Portugués':
                return 'Portuguese';
            case 'Portugues':
                return 'Portuguese';
            case 'Italiano':
                return 'Italian';
            case 'Chino':
                return 'Chinese';
            case 'Japonés':
                return 'Japanese';
            case 'Japones':
                return 'Japanese';
            case 'Ruso':
                return 'Russian';
            case 'Árabe':
                return 'Arabic';
            case 'Arabe':
                return 'Arabic';
            default:
                return $language;
        }
    }

    protected function translateLanguageToLocale($language, $locale)
    {
        if ($locale === 'en') {
            return $language;
        }
        if ($locale === 'es') {
            switch ($language) {
                case 'Spanish':
                    return 'Español';
                case 'English':
                    return 'Inglés';
                case 'French':
                    return 'Francés';
                case 'German':
                    return 'Alemán';
                case 'Portuguese':
                    return 'Portugués';
                case 'Italian':
                    return 'Italiano';
                case 'Chinese':
                    return 'Chino';
                case 'Japanese':
                    return 'Japonés';
                case 'Russian':
                    return 'Ruso';
                case 'Arabic':
                    return 'Árabe';
            }
        }

        return $language;
    }
}