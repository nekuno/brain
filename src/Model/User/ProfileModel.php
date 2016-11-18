<?php

namespace Model\User;

use Event\ProfileEvent;
use Model\Neo4j\GraphManager;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Label;
use Model\Exception\ValidationException;
use Service\Validator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProfileModel
{
    const MAX_TAGS_AND_CHOICE_LENGTH = 15;
    protected $gm;
    protected $profileFilterModel;
    protected $dispatcher;

    public function __construct(GraphManager $gm, ProfileFilterModel $profileFilterModel, EventDispatcher $dispatcher)
    {

        $this->gm = $gm;
        $this->profileFilterModel = $profileFilterModel;
        $this->dispatcher = $dispatcher;
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
            ->setParameter('id', (integer)$id)
            ->optionalMatch('(profile)<-[optionOf:OPTION_OF]-(option:ProfileOption)')
            ->optionalMatch('(profile)<-[tagged:TAGGED]-(tag:ProfileTag)')
            ->optionalMatch('(profile)-[:LOCATION]->(location:Location)')
            ->returns('collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options, collect(distinct {tag: tag, tagged: tagged}) AS tags, profile, location')
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

        $profile = $this->getById($id);
        $this->dispatcher->dispatch(\AppEvents::PROFILE_CREATED,(new ProfileEvent($profile, $id)));
        return $profile;
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
        $metadata = $this->profileFilterModel->getProfileMetadata();

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
                                if (isset($tagAndChoice['choice']) && $tagAndChoice['choice'] && !in_array($tagAndChoice['choice'], array_keys($choices))) {
                                    $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $tagAndChoice['choice'], implode("', '", array_keys($choices)));
                                }
                            }
                            break;
                        case 'multiple_choices':
                            if (!is_array($fieldValue)){
                                $fieldErrors[] = sprintf('Multiple choices option must be an array');
                                continue;
                            }
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
                                    if (!isset($fieldValue['latitude']) || !preg_match(Validator::LATITUDE_REGEX, $fieldValue['latitude'])) {
                                        $fieldErrors[] = 'Latitude not valid';
                                    } elseif (!is_float($fieldValue['latitude'])) {
                                        $fieldErrors[] = 'Latitude must be float';
                                    }
                                    if (!isset($fieldValue['longitude']) || !preg_match(Validator::LONGITUDE_REGEX, $fieldValue['longitude'])) {
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

    public function build(Row $row, $locale = null)
    {
        /* @var $node Node */
        $node = $row->offsetGet('profile');
        $profile = $node->getProperties();
        /* @var $location Node */
        $location = $row->offsetGet('location');
        if ($location && count($location->getProperties()) > 0) {
            $profile['location'] = $location->getProperties();
            if (isset($profile['location']['locality']) && $profile['location']['locality'] === 'N/A') {
                $profile['location']['locality'] = $profile['location']['address'];
            }
        } else {
            $location = null;
        }

        $profile += $this->buildOptions($row);
        $profile += $this->buildTags($row, $locale);

        return $profile;
    }

    protected function buildOptions(Row $row)
    {
        $options = $row->offsetGet('options');
        $optionsResult = array();
        $metadata = $this->profileFilterModel->getProfileMetadata();
        /** @var Row $optionData */
        foreach ($options as $optionData) {
            $option = $optionData->offsetGet('option');
            $detail = $optionData->offsetGet('detail');
            $labels = $option ? $option->getLabels() : array();
            /* @var Label $label */
            foreach ($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileOption') {
                    $typeName = $this->profileFilterModel->labelToType($label->getName());
                    if (isset($optionsResult[$typeName]) && $optionsResult[$typeName]) {
                        if (is_array($optionsResult[$typeName])) {
                            $optionsResult[$typeName] = array_merge($optionsResult[$typeName], array($option->getProperty('id')));
                        } else {
                            $optionsResult[$typeName] = array($optionsResult[$typeName], $option->getProperty('id'));
                        }
                    } else {
                        $optionsResult[$typeName] = $option->getProperty('id');
                        if (!is_null($detail)) {
                            $optionsResult[$typeName] = array();
                            $optionsResult[$typeName]['choice'] = $option->getProperty('id');
                            $optionsResult[$typeName]['detail'] = $detail;
                        }
                    }
                    if (isset($metadata[$typeName]) && $metadata[$typeName]['type'] == 'multiple_choices'
                        && !empty($optionsResult[$typeName]) && !is_array($optionsResult[$typeName])
                    ) {
                        $optionsResult[$typeName] = array($optionsResult[$typeName]);
                    }
                }
            }
        }

        return $optionsResult;
    }

    protected function buildTags(Row $row, $locale = null)
    {
        $locale = $this->profileFilterModel->getLocale($locale);
        $tags = $row->offsetGet('tags');
        $tagsResult = array();
        /** @var Row $tagData */
        foreach ($tags as $tagData) {
            $tag = $tagData->offsetGet('tag');
            $tagged = $tagData->offsetGet('tagged');
            $labels = $tag ? $tag->getLabels() : array();

            /* @var Label $label */
            foreach ($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileTag') {
                    $typeName = $this->profileFilterModel->labelToType($label->getName());
                    $tagResult = $tag->getProperty('name');
                    $detail = $tagged->getProperty('detail');
                    if (!is_null($detail)) {
                        $tagResult = array();
                        $tagResult['tag'] = $tag->getProperty('name');
                        $tagResult['choice'] = $detail;
                    }
                    if ($typeName === 'language') {
                        if (is_null($detail)) {
                            $tagResult = array();
                            $tagResult['tag'] = $tag->getProperty('name');
                            $tagResult['choice'] = '';
                        }
                        $tagResult['tag'] = $this->profileFilterModel->translateLanguageToLocale($tagResult['tag'], $locale);
                    }
                    $tagsResult[$typeName][] = $tagResult;
                }
            }
        }

        return $tagsResult;
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
        $metadata = $this->profileFilterModel->getProfileMetadata();
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
                            $qb->optionalMatch('(profile)<-[optionRel:OPTION_OF]-(:' . $this->profileFilterModel->typeToLabel($fieldName) . ')')
                                ->delete('optionRel')
                                ->with('profile');
                        }
                        if (!is_null($fieldValue)) {
                            $qb->match('(option:' . $this->profileFilterModel->typeToLabel($fieldName) . ' {id: { ' . $fieldName . ' }})')
                                ->merge('(profile)<-[:OPTION_OF]-(option)')
                                ->setParameter($fieldName, $fieldValue)
                                ->with('profile');
                        }
                        break;
                    case 'double_choice':
                        $qbDoubleChoice = $this->gm->createQueryBuilder();
                        $qbDoubleChoice->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
                            ->where('u.qnoow_id = { id }')
                            ->setParameter('id', (int)$id)
                            ->with('profile');

                        if (isset($options[$fieldName])) {
                            $qbDoubleChoice->optionalMatch('(profile)<-[doubleChoiceOptionRel:OPTION_OF]-(:' . $this->profileFilterModel->typeToLabel($fieldName) . ')')
                                ->delete('doubleChoiceOptionRel')
                                ->with('profile');
                        }
                        if (isset($fieldValue['choice'])) {
                            $detail = !is_null($fieldValue['detail']) ? $fieldValue['detail'] : '';
                            $qbDoubleChoice->match('(option:' . $this->profileFilterModel->typeToLabel($fieldName) . ' {id: { ' . $fieldName . ' }})')
                                ->merge('(profile)<-[:OPTION_OF {detail: {' . $fieldName . '_detail}}]-(option)')
                                ->setParameter($fieldName, $fieldValue['choice'])
                                ->setParameter($fieldName . '_detail', $detail);
                        }
                        $qbDoubleChoice->returns('profile');

                        $query = $qbDoubleChoice->getQuery();
                        $query->getResultSet();

                        break;
                    case 'tags_and_choice':
                        if (is_array($fieldValue)) {
                            $qbTagsAndChoice = $this->gm->createQueryBuilder();
                            $qbTagsAndChoice->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
                                ->where('u.qnoow_id = { id }')
                                ->setParameter('id', (int)$id)
                                ->with('profile');

                            $qbTagsAndChoice->optionalMatch('(profile)<-[tagsAndChoiceOptionRel:TAGGED]-(:' . $this->profileFilterModel->typeToLabel($fieldName) . ')')
                                ->delete('tagsAndChoiceOptionRel');

                            $savedTags = array();
                            foreach ($fieldValue as $index => $value) {
                                $tagValue = $fieldName === 'language' ?
                                    $this->profileFilterModel->getLanguageFromTag($value['tag']) :
                                    $value['tag'];
                                if (in_array($tagValue, $savedTags)) {
                                    continue;
                                }
                                $choice = !is_null($value['choice']) ? $value['choice'] : '';
                                $tagLabel = 'tag_' . $index;
                                $tagParameter = $fieldName . '_' . $index;
                                $choiceParameter = $fieldName . '_choice_' . $index;

                                $qbTagsAndChoice->with('profile')
                                    ->merge('(' . $tagLabel . ':ProfileTag:' . $this->profileFilterModel->typeToLabel($fieldName) . ' {name: { ' . $tagParameter . ' }})')
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
                        $qbMultipleChoices = $this->gm->createQueryBuilder();
                        $qbMultipleChoices->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
                            ->where('u.qnoow_id = { id }')
                            ->setParameter('id', (int)$id)
                            ->with('profile');

                        if (isset($options[$fieldName])) {
                            $qbMultipleChoices->optionalMatch('(profile)<-[optionRel:OPTION_OF]-(:' . $this->profileFilterModel->typeToLabel($fieldName) . ')')
                                ->delete('optionRel')
                                ->with('profile');
                        }
                        if (is_array($fieldValue)) {
                            foreach ($fieldValue as $index => $value) {
                                $qbMultipleChoices->match('(option:' . $this->profileFilterModel->typeToLabel($fieldName) . ' {id: { ' . $index . ' }})')
                                    ->merge('(profile)<-[:OPTION_OF]-(option)')
                                    ->setParameter($index, $value)
                                    ->with('profile');
                            }
                        }
                        $qbMultipleChoices->returns('profile');

                        $query = $qbMultipleChoices->getQuery();
                        //var_dump($query->getExecutableQuery());
                        $query->getResultSet();
                        break;
                    case 'tags':
                        if (isset($tags[$fieldName])) {
                            foreach ($tags[$fieldName] as $tag) {
                                $qb->optionalMatch('(profile)<-[tagRel:TAGGED]-(tag:' . $this->profileFilterModel->typeToLabel($fieldName) . ' {name: "' . $tag . '" })')
                                    ->delete('tagRel')
                                    ->with('profile');
                            }
                        }
                        if (is_array($fieldValue) && !empty($fieldValue)) {
                            foreach ($fieldValue as $tag) {
                                $qb->merge('(tag:ProfileTag:' . $this->profileFilterModel->typeToLabel($fieldName) . ' {name: "' . $tag . '" })')
                                    ->merge('(profile)<-[:TAGGED]-(tag)')
                                    ->with('profile');
                            }
                        }

                        break;
                }
            }
        }

        $qb->optionalMatch('(profile)<-[optionOf:OPTION_OF]-(option:ProfileOption)')
            ->optionalMatch('(profile)<-[tagged:TAGGED]-(tag:ProfileTag)')
            ->returns('profile', 'collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options', 'collect(distinct {tag: tag, tagged: tagged}) AS tags')
            ->limit(1);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $this->build($result->current());
    }

    protected function getProfileNodeOptions($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(option:ProfileOption)-[optionOf:OPTION_OF]->(profile:Profile)-[:PROFILE_OF]->(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $id)
            ->returns('profile, collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options');

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
        $qb->match('(tag:ProfileTag)-[tagged:TAGGED]->(profile:Profile)-[:PROFILE_OF]->(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $id)
            ->returns('profile', 'collect(distinct {tag: tag, tagged: tagged}) AS tags');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $tags = array();
        foreach ($result as $row) {
            $tags += $this->buildTags($row);
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
}