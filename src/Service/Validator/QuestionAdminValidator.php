<?php

namespace Service\Validator;

use Model\Exception\ErrorList;
use Model\Metadata\MetadataManager;

class QuestionAdminValidator extends Validator
{
    public function validateOnCreate($data)
    {
        $metadata = $this->metadata;

        $this->validateMetadata($data, $metadata);

        foreach ($data['answerTexts'] as $answerText) {
            $this->validateLocales($answerText['locales']);

        }
        $this->validateLocales($data['questionTexts']);
    }

    public function validateOnUpdate($data)
    {
        $metadata = $this->metadata;

        $this->validateMetadata($data, $metadata);

        foreach ($data['answerTexts'] as $answerText) {
            $this->validateLocales($answerText['locales']);

        }
        $this->validateLocales($data['questionTexts']);
    }
//
//    public function validateOnDelete($data)
//    {
//        $errors = array();
//        if (!isset($data['questionId'])) {
//            $errors['questionId'] = 'Question Id is not set when deleting question';
//        }
//
//        $this->throwException($errors);
//    }

    protected function validateLocales($texts)
    {
        $errorList = new ErrorList();
        $validLocales = MetadataManager::$validLocales;
        if (count($texts) < count($validLocales)){
            $errorList->addError('texts', sprintf('There are incomplete texts'));
        }
        foreach ($texts as $locale => $text) {
            if (!in_array($locale, $validLocales)) {
                $errorList->addError('texts', sprintf('Locale %s is not valid, valid locales are %s', $locale, json_encode($validLocales)));
            }

            if (!is_string($text)) {
                $errorList->addError('texts', sprintf('Texts into questions and answers must be strings'));
            }
        }

        $this->throwException($errorList);
    }
}