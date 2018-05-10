<?php

namespace Service\Validator;

use Model\Exception\ErrorList;
use Model\Metadata\MetadataManager;

class QuestionValidator extends Validator
{
    public function validateOnCreate($data)
    {
        $metadata = $this->metadata;
        $choices = $this->getChoices();
        $this->validateMetadata($data, $metadata, $choices);

        $this->validateUserInData($data, true);
    }

    public function validateOnUpdate($data)
    {
        $metadata = $this->metadata;
        $choices = $this->getChoices();
        $this->validateMetadata($data, $metadata, $choices);

        $this->validateUserInData($data, false);
    }

    public function validateOnDelete($data)
    {
        $errorList = new ErrorList();
        if (!isset($data['questionId'])) {
            $errorList->addError('questionId', 'Question Id is not set when deleting question');
        }

        $this->throwException($errorList);
    }

    protected function getChoices()
    {
        return array(
            'locale' => MetadataManager::$validLocales,
        );
    }
}