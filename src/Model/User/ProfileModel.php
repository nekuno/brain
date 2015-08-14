<?php

namespace Model\User;

use Model\Neo4j\GraphManager;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Label;
use Model\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProfileModel
{
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
        $this->validate($data);

        list($userNode, $profileNode) = $this->getUserAndProfileNodesById($id);

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
            ->setParameter('id', $id);

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

        $profile += $this->buildOptions($row);
        $profile += $this->buildTags($row);

        return $profile;
    }

    protected function buildOptions(Row $row)
    {
        $options = $row->offsetGet('options');
        $optionsResult = array();

        /** @var Node $option */
        foreach ($options as $option) {
            $labels = $option->getLabels();
            /** @var Label $label */
            foreach($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileOption') {
                    $typeName = $this->labelToType($label->getName());
                    $optionsResult[$typeName] = $option->getProperty('id');
                }
            }
        }

        return $optionsResult;

    }

    protected function buildTags(Row $row)
    {
        $tags = $row->offsetGet('tags');
        $tagsResult = array();

        /** @var Node $tag */
        foreach ($tags as $tag) {
            $labels = $tag->getLabels();
            /** @var Label $label */
            foreach($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileTag') {
                    $typeName = $this->labelToType($label->getName());
                    $tagsResult[$typeName][] = $tag->getProperty('name');
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

        $row = $result[0];
        $userNode = $row['user'];
        $profileNode = $row['profile'];

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
            ->setParameter('id', $id)
            ->with('profile');

        $dataCounter = 0;
        $tagsCounter = 0;
        foreach ($data as $fieldName => $fieldValue) {
            $dataCounter++;
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
                        $dataField = 'fieldValue_' . $dataCounter;
                        $qb->set('profile.' . $fieldName . ' = { ' . $dataField . ' }')
                            ->setParameter($dataField, $fieldValue)
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
                        $qb->merge('(profile)<-[:LOCATION]-(location:Location)')
                            ->set('location.latitude = { latitude }')
                            ->setParameter('latitude', $fieldValue['latitude'])
                            ->set('location.longitude = { longitude }')
                            ->setParameter('longitude', $fieldValue['longitude'])
                            ->set('location.address = { address }')
                            ->setParameter('address', $fieldValue['address'])
                            ->set('location.locality = { locality }')
                            ->setParameter('locality', $fieldValue['locality'])
                            ->set('location.country = { country }')
                            ->setParameter('country', $fieldValue['country'])
                            ->with('profile');
                        break;
                    case 'choice':
                        if (isset($options[$fieldName])) {
                            $qb->match('(profile)<-[optionRel:OPTION_OF]-(option:' . $this->typeToLabel($fieldName) . ')')
                                ->delete('optionRel')
                                ->with('profile');
                        }
                        if(! is_null($fieldValue)) {
                            $choiceField = 'choiceValue_' . $dataCounter;
                            $qb->match('(option:' . $this->typeToLabel($fieldName) . ' {id: { ' . $choiceField . ' }})')
                                ->merge('(profile)<-[:OPTION_OF]-(option)')
                                ->setParameter($choiceField, $fieldValue)
                                ->with('profile');
                        }
                        break;
                    case 'tags':
                        if (isset($tags[$fieldName])) {
                            foreach ($tags[$fieldName] as $tag) {
                                $tagsCounter++;
                                $totalCounter = $dataCounter + $tagsCounter;
                                $tagField = 'tagValue_' . $totalCounter;
                                $qb->match('(profile)<-[tagRel_' . $totalCounter . ':TAGGED]-(tag' . $tagsCounter . ':' . $this->typeToLabel($fieldName) . ' {name: { ' . $tagField . ' } })')
                                    ->setParameter($tagField, $tag)
                                    ->delete('tagRel_' . $totalCounter)
                                    ->with('profile');
                            }

                        }
                        if(is_array($fieldValue) && ! empty($fieldValue)) {
                            foreach ($fieldValue as $tag) {
                                $tagsCounter++;
                                $totalCounter = $dataCounter + $tagsCounter;
                                $tagField = 'tagValue_' . $totalCounter;
                                $qb->merge('(profile)<-[:TAGGED]-(tag' . $tagsCounter . ':' . $this->typeToLabel($fieldName) . ' {name: { ' . $tagField . ' } })')
                                    ->set('tag' . $tagsCounter . ':ProfileTag')
                                    ->setParameter($tagField, $tag)
                                    ->with('profile');
                            }
                        }
                        break;
                }
            }
        }

        $qb->optionalMatch('(profile)<-[:OPTION_OF]-(option:ProfileOption)')
            ->optionalMatch('(profile)-[:TAGGED]-(tag:ProfileTag)')
            ->returns('profile', 'collect(distinct option) AS options', ' collect(distinct tag) AS tags')
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
            ->returns('collect(distinct option) AS options');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $options = array();
        foreach($result as $row) {
            $options += $this->buildOptions($row, $id);
        }

        return $options;
    }

    protected function getProfileNodeTags($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(tag:ProfileTag)-[:TAGGED]-(profile:Profile)-[:PROFILE_OF]->(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $id)
            ->returns('collect(distinct tag) as tags');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $tags = array();
        foreach($result as $row) {
            $tags += $this->buildTags($row, $id);
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