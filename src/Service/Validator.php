<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Service;


use Manager\UserManager;
use Model\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Validator
{
    const MAX_TAGS_AND_CHOICE_LENGTH = 15;


    /**
     * @var array
     */
    protected $metadata;

    /**
     * @var UserManager
     */
    protected $userManager;

    public function __construct(UserManager $userManager, $metadata)
    {
        $this->metadata = $metadata;
        $this->userManager = $userManager;
    }

    public function validateEditThread(array $data, array $choices = array()) {
        return $this->validate($data, $this->metadata['threads'], $choices);
    }

    public function validateEditFilterContent(array $data, array $choices = array()) {
        return $this->validate($data, $this->metadata['filters']['content'], $choices);
    }


    protected function validate($data, $metadata, $choices)
    {
        $errors = array();
        //TODO: Build $choices as a merge of argument and choices from each metadata
        foreach ($metadata as $fieldName => $fieldData) {

            $fieldErrors = array();
            if (isset($data[$fieldName])) {

                $dataValue = $data[$fieldName];

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

                    case 'date':
                        $date = \DateTime::createFromFormat('Y-m-d', $dataValue);
                        if (!($date && $date->format('Y-m-d') == $dataValue)) {
                            $fieldErrors[] = 'Invalid date format, valid format is "Y-m-d".';
                        }
                        break;

                    case 'birthday':
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
                        if (!in_array($dataValue, array_keys($choices[$fieldName]))) {
                            $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $dataValue, implode("', '", array_keys($choices)));
                        }
                        break;

                    case 'double_choice':
                        $choices = $fieldData['choices'] + array('' => '');
                        if (!in_array($dataValue['choice'], array_keys($choices))) {
                            $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $dataValue['choice'], implode("', '", array_keys($choices)));
                        }
                        $doubleChoices = $fieldData['doubleChoices'] + array('' => '');
                        if (!isset($doubleChoices[$dataValue['choice']]) || $dataValue['detail'] && !isset($doubleChoices[$dataValue['choice']][$dataValue['detail']])) {
                            $fieldErrors[] = sprintf('Option choice and detail must be set in "%s"', $dataValue['choice']);
                        } elseif ($dataValue['detail'] && !in_array($dataValue['detail'], array_keys($doubleChoices[$dataValue['choice']]))) {
                            $fieldErrors[] = sprintf('Detail with value "%s" is not valid, possible values are "%s"', $dataValue['detail'], implode("', '", array_keys($doubleChoices)));
                        }
                        break;
                    case 'tags':
                        break;
                    case 'tags_and_choice':
                        if (!is_array($dataValue)){
                            $fieldErrors[] = 'Tags and choice value must be an array';
                        }
                        $choices = $fieldData['choices'];
                        if (count($dataValue) > self::MAX_TAGS_AND_CHOICE_LENGTH) {
                            $fieldErrors[] = sprintf('Tags and choice length "%s" is too long. "%s" is the maximum', count($dataValue), self::MAX_TAGS_AND_CHOICE_LENGTH);
                        }
                        foreach ($dataValue as $tagAndChoice) {
                            if (!isset($tagAndChoice['tag']) || !array_key_exists('choice', $tagAndChoice)) {
                                $fieldErrors[] = sprintf('Tag and choice must be defined for tags and choice type');
                            }
                            if (isset($tagAndChoice['choice']) && !in_array($tagAndChoice['choice'], array_keys($choices))) {
                                $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $tagAndChoice['choice'], implode("', '", array_keys($choices)));
                            }
                        }
                        break;
                    case 'multiple_choices':
                        if (!is_array($dataValue)){
                            $fieldErrors[] = 'Multiple choices value must be an array';
                        }
                        $choices = $fieldData['choices'];
                        if (count($dataValue) > $fieldData['max_choices']) {
                            $fieldErrors[] = sprintf('Option length "%s" is too long. "%s" is the maximum', count($dataValue), $fieldData['max_choices']);
                        }
                        foreach($dataValue as $value) {
                            if (!in_array($value, array_keys($choices))) {
                                $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $value, implode("', '", array_keys($choices)));
                            }
                        }
                        break;
                    case 'location':
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
                        break;
                }
            } else {
                if ($fieldData['required'] === true) {
                    $fieldErrors[] = 'It\'s required.';
                }
            }

            if (count($fieldErrors) > 0) {
                $errors[$fieldName] = $fieldErrors;
            }
        }

        if (isset($data['userId'])) {
            try {
                $this->userManager->getById((integer)$data['userId'], true);
            } catch (NotFoundHttpException $e) {
                $errors['userId'] = array($e->getMessage());
            }
        } else {
            $errors['userId'] = array('User identification not supplied');
        }

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        return true;
    }

}