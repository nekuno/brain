<?php

namespace Model\User;

use Model\Neo4j\GraphManager;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProfileModel
{
    protected $client;
    protected $metadata;
    protected $defaultLocale;
    protected $userId;

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

        foreach ($metadata as $key => &$item) {
            if (isset($item['labelFilter'])) {
                $item['label'] = $item['labelFilter'][$locale];
                unset($item['labelFilter']);
            }
            if (isset($item['filterable']) && $item['filterable'] === false) {
                unset($metadata[$key]);
            }
        }

        return $metadata;
    }

    /**
     * @param int $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function getById($id)
    {
        $this->userId = (int)$id;

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[:PROFILE_OF]-(profile:Profile)')
            ->where('user.qnoow_id = {id}')
            ->optionalMatch('(profile)<-[:OPTION_OF]-(option:ProfileOption)')
            ->with('profile, collect(option) AS options')
            ->optionalMatch('(profile)<-[:TAGGED]-(tag:ProfileTag)')
            ->optionalMatch('(profile)-[:LOCATION]->(location:Location)')
            ->returns('profile, location, options, collect(tag) as tags')
            ->limit(1)
            ->setParameter('id', $this->userId);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Profile not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    /**
     * @param $id
     * @param array $data
     * @return array
     * @throws NotFoundHttpException|MethodNotAllowedHttpException
     */
    public function create($id, array $data)
    {
        $this->userId = (int)$id;

        $this->validate($data);

        list($userNode, $profileNode) = $this->getUserAndProfileNodesById();

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if ($profileNode instanceof Node) {
            throw new MethodNotAllowedHttpException(array('PUT'), 'Profile already exists');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)', '(profile:Profile)')
            ->where('user.qnoow_id = { id }')
            ->createUnique('(profile)-[po:PROFILE_OF]->(user)')
            ->setParameter('id', $this->userId);

        $qb->getQuery()->getResultSet();

        $this->saveProfileData($data);

        return $this->getById($this->userId);
    }

    /**
     * @param integer $id
     * @param array $data
     * @return array
     * @throws ValidationException|NotFoundHttpException
     */
    public function update($id, array $data)
    {
        $this->userId = (int)$id;

        $this->validate($data);

        list($userNode, $profileNode) = $this->getUserAndProfileNodesById();

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if (!($profileNode instanceof Node)) {
            throw new NotFoundHttpException('Profile not found');
        }

        $this->saveProfileData($data);

        return $this->getById($this->userId);
    }

    /**
     * @param $id
     */
    public function remove($id)
    {
        $this->userId = (int)$id;

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[po:PROFILE_OF]-(profile:Profile)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $this->userId)
            ->delete('po, profile');

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

                        case 'location':
                            if (!is_array($fieldValue)) {
                                $fieldErrors[] = sprintf('The value "%s" is not valid, it should be an array with "latitude" and "longitude" keys', $fieldValue);
                            } else {
                                if (!isset($fieldValue['address']) || !$fieldValue['address'] || !is_string($fieldValue['address'])) {
                                    $fieldErrors[] = 'Address required';
                                } else {
                                    if (!isset($fieldValue['latitude']) || !preg_match("/^-?([1-8]?[1-9]|[1-9]0)\.{1}\d+$/", $fieldValue['latitude'])) {
                                        $fieldErrors[] = 'Latitude not valid';
                                    } elseif (!is_float($fieldValue['latitude'])) {
                                        $fieldErrors[] = 'Latitude must be float';
                                    }
                                    if (!isset($fieldValue['longitude']) || !preg_match("/^-?([1]?[1-7][1-9]|[1]?[1-8][0]|[1-9]?[0-9])\.{1}\d+$/", $fieldValue['longitude'])) {
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
            $e = new ValidationException('Validation error');
            $e->setErrors($errors);
            throw $e;
        }
    }

    protected function build(Row $row)
    {
        /* @var $node Node */
        $node = $row->offsetGet('profile');
        $profile = $node->getProperties();

        /* @var $location Node */
        $location = $row->offsetGet('location');
        if ($location) {
            $profile['location'] = $location->getProperties();
        }

        foreach ($row->offsetGet('options') as $option) {
            /* @var $option Node */
            $labels = $option->getLabels();
            foreach ($labels as $label) {
                /* @var $label Label */
                $labelName = $label->getName();
                if ($labelName != 'ProfileOption') {
                    $typeName = $this->labelToType($labelName);
                    $profile[$typeName] = $option->getProperty('id');
                }

            }
        }

        foreach ($row->offsetGet('tags') as $tag) {
            /* @var $tag Node */
            $labels = $tag->getLabels();
            foreach ($labels as $label) {
                /* @var $label Label */
                $labelName = $label->getName();
                if ($labelName != 'ProfileTag') {
                    $typeName = $this->labelToType($labelName);
                    $profile[$typeName][] = $tag->getProperty('name');
                }

            }
        }

        return $profile;
    }

    protected function buildOptions(Row $row)
    {
        $options = $row->offsetGet('options');
        $optionsResult = array();

        foreach ($options as $option) {
            $qb = $this->gm->createQueryBuilder();
            $qb->match('(option:ProfileOption)-[:OPTION_OF]-(profile:Profile)-[:PROFILE_OF]->(user:User)')
                ->where('user.qnoow_id = { id } AND id(option) = { optionId }')
                ->setParameters(array(
                    'id' => $this->userId,
                    'optionId' => isset($option['id']) ? $option['id'] : null,
                ))
                ->returns('label(option) AS label');

            $query = $qb->getQuery();
            $result = $query->getResultSet();
            foreach($result as $row) {
                $label = $row->offsetGet('label');
                if ($label && $label != 'ProfileOption') {
                    $typeName = $this->labelToType($label);
                    $optionsResult[$typeName] = $option;
                }
            }
        }

        return $optionsResult;

    }
    protected function buildTags(Row $row)
    {
        $tags = $row->offsetGet('tags');
        $tagsResult = array();

        foreach ($tags as $tag) {
            $qb = $this->gm->createQueryBuilder();
            $qb->match('(tag:Tag)-[:TAGGED]-(profile:Profile)-[:PROFILE_OF]->(user:User)')
                ->where('user.qnoow_id = { id } AND tag.name = { tagName }')
                ->setParameters(array(
                    'id' => $this->userId,
                    'tagName' => isset($tag['name']) ? $tag['name'] : null,
                ))
                ->returns('label(tag) AS label');

            $query = $qb->getQuery();
            $result = $query->getResultSet();
            foreach($result as $row) {
                $label = $row->offsetGet('label');
                if ($label && $label != 'ProfileTag') {
                    $typeName = $this->labelToType($label);
                    if (!isset($tags[$typeName])) {
                        $tags[$typeName] = array();
                    }
                    $tagsResult[$typeName][] = $tag;
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
        foreach ($result as $row) {
            $typeName = $this->labelToType($row['labelName']);
            $optionId = $row['id'];
            $optionName = $row['name'];

            $choiceOptions[$typeName][$optionId] = $optionName;
        }

        return $choiceOptions;
    }

    protected function getUserAndProfileNodesById()
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)')
            ->where('user.qnoow_id = { id }')
            ->optionalMatch('(user)<-[:PROFILE_OF]-(profile:Profile)')
            ->setParameter('id', $this->userId)
            ->returns('user', 'profile')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if (count($result) < 1) {
            return array(null, null);
        }

        $row = $result[0];
        $userNode = $row['user'];
        $profileNode = $row['profile'];

        return array($userNode, $profileNode);
    }

    protected function saveProfileData(array $data)
    {
        $metadata = $this->getMetadata();
        $options = $this->getProfileNodeOptions();
        $tags = $this->getProfileNodeTags();

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', $this->userId)
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
                        $qb->set('profile.' . $fieldName . ' = "' . $fieldValue . '"')
                            ->with('profile');
                        break;
                    case 'boolean':
                        $booleanFieldValue = $fieldValue ? 'true' : 'false';
                        $qb->set('profile.' . $fieldName . ' = ' . $booleanFieldValue)
                            ->with('profile');
                        break;
                    case 'integer':
                        $qb->set('profile.' . $fieldName . ' = ' . $fieldValue)
                            ->with('profile');
                        break;

                    case 'birthday':
                        $zodiacSign = $this->getZodiacSignFromDate($fieldValue);
                        if (isset($options['zodiacSign']) && is_null($zodiacSign)) {
                            $qb->match('(profile)<-[rel:OPTION_OF]-(zs:ZodiacSign)')
                                ->delete('rel')
                                ->with('profile');
                        }
                        elseif (!is_null($zodiacSign)) {
                            $qb->createUnique('(profile)<-[:OPTION_OF]-(newZs:ZodiacSign {id: "' . $zodiacSign . '"})')
                                ->with('profile');
                        }

                        $qb->set('profile.' . $fieldName . ' = "' . $fieldValue . '"')
                            ->with('profile');
                        break;
                    case 'location':
                        $qb->merge('(profile)<-[:LOCATION]-(location:Location)')
                            ->set('location.latitude = ' . $fieldValue['latitude'])
                            ->set('location.longitude = ' . $fieldValue['longitude'])
                            ->set('location.address = "' . $fieldValue['address'] . '"')
                            ->set('location.locality = "' . $fieldValue['locality'] . '"')
                            ->set('location.country = "' . $fieldValue['country'] . '"')
                            ->with('profile');
                        break;
                    case 'choice':
                        if (isset($options[$fieldName]) && is_null($fieldValue)) {
                            $qb->match('(profile)<-[rel:OPTION_OF]-(option:' . $this->labelToType($fieldName))
                                ->delete('rel')
                                ->with('profile');
                        }
                        elseif(! is_null($fieldValue)) {
                            $qb->merge('(profile)<-[:OPTION_OF]-(option:' . $this->labelToType($fieldName) . ' {name: "' . $fieldValue . '" })')
                                ->with('profile');
                        }
                        break;
                    case 'tags':
                        if (isset($tags[$fieldName]) && is_null($fieldValue)) {
                            $qb->match('(profile)<-[rel:TAGGED]-(tag:' . $this->labelToType($fieldName))
                                ->delete('rel')
                                ->with('profile');
                        }
                        elseif(! is_null($fieldValue)) {
                            foreach ($tags[$fieldName] as $tag) {
                                $qb->merge('(profile)<-[:TAGGED]-(tag:' . $this->labelToType($fieldName) . ' {name: "' . $tag['name'] . '" })')
                                    ->with('profile');
                            }
                        }
                        break;
                }
            }
        }

        $qb->returns('profile')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->build($result->current());
    }

    protected function getProfileNodeOptions()
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(option)-[:OPTIONS_OF]-(profile:Profile)-[:PROFILE_OF]->(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $this->userId)
            ->returns('option');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $options = array();
        foreach($result as $row) {
            $options += $this->buildOptions($row);
        }

        return $options;
    }

    protected function getProfileNodeTags()
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(tag:ProfileTag)-[:TAGGED]-(profile:Profile)-[:PROFILE_OF]->(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $this->userId)
            ->returns('tag');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $tags = array();
        foreach($result as $row) {
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
            /* @var $row \Everyman\Neo4j\Query\Row */
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

}