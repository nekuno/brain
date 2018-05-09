<?php

namespace Service\Validator;

class ProfileValidator extends Validator
{
    public function validateOnCreate($data)
    {
        $this->validateUserInData($data, false);

        return $this->validate($data);
    }

    public function validateOnUpdate($data)
    {
        $this->validateUserInData($data);

        return $this->validate($data);
    }

    public function validateOnDelete($data)
    {
        $this->validateUserInData($data);
    }

    protected function validate(array $data)
    {
        $metadata = $this->metadata;
        $choices = $data['choices'];
//        $this->fixOrientationRequired($data, $metadata);

        return $this->validateMetadata($data, $metadata, $choices);
    }

    protected function fixOrientationRequired($data, &$metadata)
    {
        $isOrientationRequiredFalse = isset($data['orientationRequired']) && $data['orientationRequired'] === false;
        $metadata['orientation']['required'] = !$isOrientationRequiredFalse;
    }
}