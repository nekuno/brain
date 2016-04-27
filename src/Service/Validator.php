<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Service;


use Manager\UserManager;
use Model\Exception\ValidationException;
use Model\User\ContentFilterModel;
use Model\User\ProfileFilterModel;
use Model\User\UserFilterModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Validator
{
    const MAX_TAGS_AND_CHOICE_LENGTH = 15;

    /**
     * @var array
     */
    protected $metadata;

    /**
     * @var ProfileFilterModel
     */
    protected $profileFilterModel;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var UserFilterModel
     */
    protected $userFilterModel;

    /**
     * @var ContentFilterModel
     */
    protected $contentFilterModel;

    public function __construct(UserManager $userManager,
                                ProfileFilterModel $profileFilterModel,
                                UserFilterModel $userFilterModel,
                                ContentFilterModel $contentFilterModel,
                                array $metadata)
    {
        $this->metadata = $metadata;
        $this->profileFilterModel = $profileFilterModel;
        $this->userFilterModel = $userFilterModel;
        $this->contentFilterModel = $contentFilterModel;
        $this->userManager = $userManager;
    }

    public function validateUserId($userId)
    {
        $errors = array();

        if (empty($userId)) {
            $errors['userId'] = array('User identification not supplied');
        }

        try {
            $this->userManager->getById((integer)$userId, true);
        } catch (NotFoundHttpException $e) {
            $errors['userId'] = array($e->getMessage());
        }

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    public function validateEditThread(array $data, array $choices = array())
    {
        return $this->validate($data, $this->metadata['threads'], $choices);
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
                        if (!is_array($dataValue)){
                            $fieldErrors[] = 'Must be an array';
                            continue;
                        }
                        if(!isset($dataValue['max'])){
                            $fieldErrors[] = 'There must be a max value';
                            continue;
                        }
                        if (!is_int($dataValue['max'])){
                            $fieldErrors[] = 'Maximum value must be an integer';
                        }
                        if(!isset($dataValue['min'])){
                            $fieldErrors[] = 'There must be a min value';
                            continue;
                        }
                        if (!is_int($dataValue['min'])){
                            $fieldErrors[] = 'Minimum value must be an integer';
                        }
                        if (isset($fieldData['min'])) {
                            if ($dataValue['min'] < $fieldData['min']) {
                                $fieldErrors[] = 'Minimum value must be greater than ' . $fieldData['min'];
                            }
                        }
                        if (isset($fieldData['max'])) {
                            if ($dataValue['max'] > $fieldData['max']) {
                                $fieldErrors[] = 'Maximum value must be less than ' . $fieldData['max'];
                            }
                        }
                        if ($dataValue['min'] > $dataValue['max']){
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
                        if (!is_string($dataValue)){
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
                        $choices = $choices[$fieldName] + array('' => '');
                        if (!in_array($dataValue['choice'], $choices)) {
                            $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $dataValue['choice'], implode("', '", $choices));
                        }
                        $doubleChoices = $fieldData['doubleChoices'] + array('' => '');
                        if (!isset($doubleChoices[$dataValue['choice']]) || $dataValue['detail'] && !isset($doubleChoices[$dataValue['choice']][$dataValue['detail']])) {
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
                        $choices = $choices[$fieldName] + array('' => '');
                        $doubleChoices = $fieldData['doubleChoices'] + array('' => '');
                        foreach ($dataValue as $singleDataValue){
                            if (!in_array($singleDataValue['choice'], $choices)) {
                                $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $singleDataValue['choice'], implode("', '", $choices));
                            }
                            if (!isset($doubleChoices[$singleDataValue['choice']]) || $singleDataValue['detail'] && !isset($doubleChoices[$singleDataValue['choice']][$singleDataValue['detail']])) {
                                $fieldErrors[] = sprintf('Option choice and detail must be set in "%s"', $singleDataValue['choice']);
                            } elseif ($singleDataValue['detail'] && !in_array($singleDataValue['detail'], array_keys($doubleChoices[$singleDataValue['choice']]))) {
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
                        $multipleChoices = $choices[$fieldName] + array('');
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
                        foreach ($this->validateLocation($dataValue) as $error){
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

                        foreach ($this->validateLocation($dataValue['location']) as $error){
                            $fieldErrors[] = $error;
                        }
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

    private function validateLocation($dataValue){
        $fieldErrors = array();
        if (!is_array($dataValue)) {
            $fieldErrors[] = sprintf('The value "%s" is not valid, it should be an array with "latitude" and "longitude" keys', $dataValue);
        } else {
            if (!isset($dataValue['address']) || !$dataValue['address'] || !is_string($dataValue['address'])) {
                $fieldErrors[] = 'Address required';
            } else {
                if (!isset($dataValue['latitude']) || !preg_match("/^-?([1-8]?[0-9]|[1-9]0)\.{1}\d+$/", $dataValue['latitude'])) {
                    $fieldErrors[] = 'Latitude not valid';
                } elseif (!is_float($dataValue['latitude'])) {
                    $fieldErrors[] = 'Latitude must be float';
                }
                if (!isset($dataValue['longitude']) || !preg_match("/^-?([1]?[0-7][1-9]|[1]?[1-8][0]|[1-9]?[0-9])\.{1}\d+$/", $dataValue['longitude'])) {
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