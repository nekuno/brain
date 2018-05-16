<?php

namespace Service\Validator;

use Model\Link\LinkManager;

class FilterContentValidator extends Validator
{
    public function validateOnUpdate($data)
    {
        $metadata = $this->metadata;
        $choices = $this->getChoices();
        $this->validateMetadata($data, $metadata, $choices);

        $this->validateUserInData($data, false);
    }

    public function validateOnContent($data)
    {
        $this->validateUserInData($data, true);
    }

    protected function getChoices()
    {
        return array('type' => LinkManager::getValidTypes());
    }

}