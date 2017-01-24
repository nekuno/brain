<?php

namespace Service;

use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\User\ContentFilterModel;
use Model\User\ProfileFilterModel;
use Model\User\UserFilterModel;

class Validator
{
    const MAX_TAGS_AND_CHOICE_LENGTH = 15;
    const LATITUDE_REGEX = '/^-?([1-8]?[0-9]|[1-9]0)\.{1}\d+$/';
    const LONGITUDE_REGEX = '/^-?([1]?[0-7][1-9]|[1]?[1-8][0]|[1-9]?[0-9])\.{1}\d+$/';

    /**
     * @var array
     */
    protected $metadata;

    /**
     * @var ProfileFilterModel
     */
    protected $profileFilterModel;

    /**
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * @var UserFilterModel
     */
    protected $userFilterModel;

    /**
     * @var ContentFilterModel
     */
    protected $contentFilterModel;

    public function __construct(
        GraphManager $graphManager,
        ProfileFilterModel $profileFilterModel,
        UserFilterModel $userFilterModel,
        ContentFilterModel $contentFilterModel,
        array $metadata
    ) {
        $this->metadata = $metadata;
        $this->profileFilterModel = $profileFilterModel;
        $this->userFilterModel = $userFilterModel;
        $this->contentFilterModel = $contentFilterModel;
        $this->graphManager = $graphManager;
    }

    public function validateUserId($userId)
    {
        $errors = array('userId' => array());

        if (!is_int($userId)) {
            $errors['userId'][] = array('User Id must be an integer');
        }

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User)')
            ->where('u.qnoow_id = {userId}')
            ->setParameter('userId', $userId);
        $qb->returns('u.qnoow_id');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            $errors['userId'][] = array(sprintf('User with id %d not found', $userId));
        }

        if (count($errors['userId']) > 0) {
            throw new ValidationException($errors);
        }
    }

    public function validateGroupId($groupId)
    {
        $errors = array('groupId' => array());

        if (!is_int($groupId)) {
            $errors['groupId'][] = array('Group Id must be an integer');
        }

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(g:Group)')
            ->where('id(g) = {groupId}')
            ->setParameter('groupId', $groupId);
        $qb->returns('id(g)');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            $errors['groupId'][] = array(sprintf('Group with id %d not found', $groupId));
        }

        if (count($errors['groupId']) > 0) {
            throw new ValidationException($errors);
        }
    }

    public function validateEditThread(array $data, array $choices = array())
    {
        return $this->validate($data, $this->metadata['threads'], $choices);
    }

    public function validateGroup(array $data)
    {
        $this->validate($data, $this->metadata['groups']);

        $errors = array();
        if (isset($data['followers']) && $data['followers']) {
            if (!is_bool($data['followers'])) {
                $errors['followers'] = array('"followers" must be boolean');
            }
            if (!isset($data['influencer_id'])) {
                $errors['influencer_id'] = array('"influencer_id" is required for followers groups');
            } elseif (!is_int($data['influencer_id'])) {
                $errors['influencer_id'] = array('"influencer_id" must be integer');
            }
            if (!isset($data['min_matching'])) {
                $errors['min_matching'] = array('"min_matching" is required for followers groups');
            } elseif (!is_int($data['min_matching'])) {
                $errors['min_matching'] = array('"min_matching" must be integer');
            }
            if (!isset($data['type_matching'])) {
                $errors['type_matching'] = array('"type_matching" is required for followers groups');
            } elseif ($data['type_matching'] !== 'similarity' && $data['type_matching'] !== 'compatibility') {
                $errors['type_matching'] = array('"type_matching" must be "similarity" or "compatibility"');
            }
        }

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    public function validateInvitation(array $data, $invitationIdRequired = false)
    {
        $metadata = $this->metadata['invitations'];

        if ($invitationIdRequired) {
            $metadata['invitationId']['required'] = true;
        }

        if (isset($data['groupId'])) {
            $groupId = $data['groupId'];
            if (!(is_int($groupId) || is_double($groupId))) {
                $fieldErrors[] = 'groupId must be an integer';
            }
            $this->validateGroupId($groupId);
        }

        if (isset($data['userId'])) {
            $this->validateUserId($data['userId']);
        }

        $this->validate($data, $metadata);
    }

    public function validateEditFilterContent(array $data, array $choices = array())
    {
        return $this->validate($data, $this->contentFilterModel->getFilters(), $choices);
    }

    public function validateEditFilterUsers($data, $choices = array())
    {
        return $this->validate($data, $this->userFilterModel->getFilters(), $choices);
    }

    public function validateEditFilterProfile($data, $choices = array())
    {
        return $this->validate($data, $this->profileFilterModel->getFilters(), $choices);
    }

    public function validateRecommendateContent($data, $choices = array())
    {
        return $this->validate($data, $this->contentFilterModel->getFilters(), $choices);
    }

    protected function validate($data, $metadata, $dataChoices = array())
    {
        $errors = array();
        //TODO: Build $choices as a merge of argument and choices from each metadata
        foreach ($metadata as $fieldName => $fieldData) {

            $fieldErrors = array();
            if (isset($data[$fieldName])) {

                $dataValue = $data[$fieldName];
                $choices = array_merge($dataChoices, isset($fieldData['choices']) ? $fieldData['choices'] : array());

                switch ($fieldData['type']) {
                    case 'text':
                    case 'textarea':
                        if (isset($fieldData['min'])) {
                            if (strlen($dataValue) < $fieldData['min']) {
                                $fieldErrors[] = 'Must have ' . $fieldData['min'] . ' characters min.';
                            }
                        }
                        if (isset($fieldData['max'])) {
                            if (strlen($dataValue) > $fieldData['max']) {
                                $fieldErrors[] = 'Must have ' . $fieldData['max'] . ' characters max.';
                            }
                        }
                        break;

                    case 'integer':
                        if (!is_integer($dataValue)) {
                            $fieldErrors[] = 'Must be an integer';
                        }
                        if (isset($fieldData['min'])) {
                            if (!empty($dataValue) && $dataValue < $fieldData['min']) {
                                $fieldErrors[] = 'Must be greater than ' . $fieldData['min'];
                            }
                        }
                        if (isset($fieldData['max'])) {
                            if ($dataValue > $fieldData['max']) {
                                $fieldErrors[] = 'Must be less than ' . $fieldData['max'];
                            }
                        }
                        break;

                    case 'birthday_range':
                    case 'integer_range':
                        if (!is_array($dataValue)) {
                            $fieldErrors[] = 'Must be an array';
                            continue;
                        }
                        if (isset($dataValue['max']) && !is_int($dataValue['max'])) {
                            $fieldErrors[] = 'Maximum value must be an integer';
                        }
                        if (isset($dataValue['min']) && !is_int($dataValue['min'])) {
                            $fieldErrors[] = 'Minimum value must be an integer';
                        }
                        if (isset($fieldData['min']) && isset($dataValue['min']) && $dataValue['min'] < $fieldData['min']) {
                            $fieldErrors[] = 'Minimum value must be greater than ' . $fieldData['min'];
                        }
                        if (isset($fieldData['max']) && isset($dataValue['max']) && $dataValue['max'] > $fieldData['max']) {
                            $fieldErrors[] = 'Maximum value must be less than ' . $fieldData['max'];
                        }
                        if (isset($dataValue['min']) && isset($dataValue['max']) && $dataValue['min'] > $dataValue['max']) {
                            $fieldErrors[] = 'Minimum value must be smaller or equal than maximum value';
                        }
                        break;

                    case 'date':
                        $date = \DateTime::createFromFormat('Y-m-d', $dataValue);
                        if (!($date && $date->format('Y-m-d') == $dataValue)) {
                            $fieldErrors[] = 'Invalid date format, valid format is "Y-m-d".';
                        }
                        break;

                    case 'birthday':
                        if (!is_string($dataValue)) {
                            $fieldErrors[] = 'Birthday value must be a string';
                            continue;
                        }
                        $date = \DateTime::createFromFormat('Y-m-d', $dataValue);
                        $now = new \DateTime();
                        if (!($date && $date->format('Y-m-d') == $dataValue)) {
                            $fieldErrors[] = 'Invalid date format, valid format is "YYYY-MM-DD".';
                        } elseif ($now < $date) {
                            $fieldErrors[] = 'Invalid birthday date, can not be in the future.';
                        } elseif ($now->modify('-14 year') < $date) {
                            $fieldErrors[] = 'Invalid birthday date, you must be older than 14 years.';
                        }
                        break;

                    case 'boolean':
                        if ($dataValue !== true && $dataValue !== false) {
                            $fieldErrors[] = 'Must be a boolean.';
                        }
                        break;

                    case 'choice':
                        if (!in_array($dataValue, $choices[$fieldName])) {
                            $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $dataValue, implode("', '", $choices[$fieldName]));
                        }
                        break;

                    case 'double_choice':
                        $thisChoices = $choices[$fieldName] + array('' => '');
                        if (!in_array($dataValue['choice'], $thisChoices)) {
                            $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $dataValue['choice'], implode("', '", $thisChoices));
                        }
                        $doubleChoices = $fieldData['doubleChoices'] + array('' => '');
                        if (!isset($doubleChoices[$dataValue['choice']]) || isset($dataValue['detail']) && !isset($doubleChoices[$dataValue['choice']][$dataValue['detail']])) {
                            $fieldErrors[] = sprintf('Option choice and detail must be set in "%s"', $dataValue['choice']);
                        } elseif ($dataValue['detail'] && !in_array($dataValue['detail'], array_keys($doubleChoices[$dataValue['choice']]))) {
                            $fieldErrors[] = sprintf('Detail with value "%s" is not valid, possible values are "%s"', $dataValue['detail'], implode("', '", array_keys($doubleChoices)));
                        }
                        break;
                    case 'double_multiple_choices':
                        if (!is_array($dataValue)) {
                            $fieldErrors[] = 'Multiple choices value must be an array';
                            continue;
                        }
                        $thisChoices = $choices[$fieldName] + array('' => '');
                        $doubleChoices = $fieldData['doubleChoices'] + array('' => '');
                        foreach ($dataValue as $singleDataValue) {
                            if (!in_array($singleDataValue['choice'], $thisChoices)) {
                                $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $singleDataValue['choice'], implode("', '", $thisChoices));
                            }
                            if (!isset($doubleChoices[$singleDataValue['choice']]) || isset($singleDataValue['detail']) && !isset($doubleChoices[$singleDataValue['choice']][$singleDataValue['detail']])) {
                                $fieldErrors[] = sprintf('Option choice and detail must be set in "%s"', $singleDataValue['choice']);
                            } elseif (isset($singleDataValue['detail']) && !in_array($singleDataValue['detail'], array_keys($doubleChoices[$singleDataValue['choice']]))) {
                                $fieldErrors[] = sprintf('Detail with value "%s" is not valid, possible values are "%s"', $singleDataValue['detail'], implode("', '", array_keys($doubleChoices)));
                            }
                        }
                        break;
                    case 'tags':
                        break;
                    case 'tags_and_choice':
                        if (!is_array($dataValue)) {
                            $fieldErrors[] = 'Tags and choice value must be an array';
                        }
                        if (count($dataValue) > self::MAX_TAGS_AND_CHOICE_LENGTH) {
                            $fieldErrors[] = sprintf('Tags and choice length "%s" is too long. "%s" is the maximum', count($dataValue), self::MAX_TAGS_AND_CHOICE_LENGTH);
                        }
                        foreach ($dataValue as $tagAndChoice) {
                            if (!isset($tagAndChoice['tag']) || !array_key_exists('choice', $tagAndChoice)) {
                                $fieldErrors[] = sprintf('Tag and choice must be defined for tags and choice type');
                            }
                            if (isset($tagAndChoice['choice']) && isset($choices[$fieldName]) && !in_array($tagAndChoice['choice'], array_keys($choices[$fieldName]))) {
                                $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $tagAndChoice['choice'], implode("', '", array_keys($choices)));
                            }
                        }
                        break;
                    case 'multiple_choices':
                        $multipleChoices = $choices[$fieldName];
                        if (!is_array($dataValue)) {
                            $fieldErrors[] = 'Multiple choices value must be an array';
                            continue;
                        }

                        if (count($dataValue) > $fieldData['max_choices']) {
                            $fieldErrors[] = sprintf('Option length "%s" is too long. "%s" is the maximum', count($dataValue), $fieldData['max_choices']);
                        }
                        foreach ($dataValue as $singleValue) {
                            if (!in_array($singleValue, $multipleChoices)) {
                                $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $singleValue, implode("', '", $multipleChoices));
                            }
                        }
                        break;
                    case 'location':
                        foreach ($this->validateLocation($dataValue) as $error) {
                            $fieldErrors[] = $error;
                        }

                        break;
                    case 'location_distance':
                        if (!is_array($dataValue)) {
                            $fieldErrors[] = 'The location distance value must be an array';
                            continue;
                        }
                        if (!isset($dataValue['distance'])) {
                            $fieldErrors[] = 'Distance required';
                        }
                        if (!isset($dataValue['location'])) {
                            $fieldErrors[] = 'Location required';
                            continue;
                        }

                        foreach ($this->validateLocation($dataValue['location']) as $error) {
                            $fieldErrors[] = $error;
                        }
                        break;
                    case 'email':
                        if (!filter_var($dataValue, FILTER_VALIDATE_EMAIL)) {
                            $fieldErrors[] = 'Value must be a valid email';
                        }
                        break;
                    case 'url':
                        if (!filter_var($dataValue, FILTER_VALIDATE_URL)) {
                            $fieldErrors[] = 'Value must be a valid URL';
                        }
                        break;
                    case 'image_path':
                        if (!preg_match('/^[\w\/\\-]+\.(png|jpe?g|gif|tiff)$/i', $dataValue)) {
                            $fieldErrors[] = 'Value must be a valid path';
                        }
                        break;
                    case 'timestamp':
                        if (!(is_int($dataValue) || is_double($dataValue))) {
                            $fieldErrors[] = 'Value must be a valid timestamp';
                        }
                        break;
                    case 'string':
                        if (!is_string($dataValue)) {
                            $fieldErrors[] = 'Value must be a string';
                        }
                        break;
                    case 'order':
                        if (!in_array($dataValue, array('similarity', 'matching'))) {
                            $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $dataValue, implode("', '", array('similarity', 'matching')));
                        }
                        break;
                }
            } else {
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

        return true;
    }

    private function validateLocation($dataValue)
    {
        $fieldErrors = array();
        if (!is_array($dataValue)) {
            $fieldErrors[] = sprintf('The value "%s" is not valid, it should be an array with "latitude" and "longitude" keys', $dataValue);
        } else {
            if (!isset($dataValue['address']) || !$dataValue['address'] || !is_string($dataValue['address'])) {
                $fieldErrors[] = 'Address required';
            } else {
                if (!isset($dataValue['latitude']) || !preg_match(Validator::LATITUDE_REGEX, $dataValue['latitude'])) {
                    $fieldErrors[] = 'Latitude not valid';
                } elseif (!is_float($dataValue['latitude'])) {
                    $fieldErrors[] = 'Latitude must be float';
                }
                if (!isset($dataValue['longitude']) || !preg_match(Validator::LONGITUDE_REGEX, $dataValue['longitude'])) {
                    $fieldErrors[] = 'Longitude not valid';
                } elseif (!is_float($dataValue['longitude'])) {
                    $fieldErrors[] = 'Longitude must be float';
                }
                if (!isset($dataValue['locality']) || !$dataValue['locality'] || !is_string($dataValue['locality'])) {
                    $fieldErrors[] = 'Locality required';
                }
                if (!isset($dataValue['country']) || !$dataValue['country'] || !is_string($dataValue['country'])) {
                    $fieldErrors[] = 'Country required';
                }
            }
        }

        return $fieldErrors;
    }

}