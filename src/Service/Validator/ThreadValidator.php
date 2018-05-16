<?php

namespace Service\Validator;


use Model\Thread\ThreadManager;

class ThreadValidator extends Validator
{
    public function validateOnCreate($data)
    {
        $this->validateUserInData($data, false);

        $metadata = $this->metadata;
        $choices = $this->getChoices();
        $this->validateMetadata($data, $metadata, $choices);

    }

    public function validateOnUpdate($data)
    {
        $this->validateUserInData($data, false);

        $metadata = $this->metadata;
        $choices = $this->getChoices();
        $this->validateMetadata($data, $metadata, $choices);

    }

    public function validateOnDelete($data)
    {

    }

    private function getChoices()
    {
        return array(
            'category' => array(
                ThreadManager::LABEL_THREAD_USERS,
                ThreadManager::LABEL_THREAD_CONTENT
            )
        );
    }
}