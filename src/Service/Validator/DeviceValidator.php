<?php

namespace Service\Validator;

use Model\Exception\ErrorList;

class DeviceValidator extends Validator
{
    public function validateOnCreate($data)
    {
        $metadata = $this->metadata;
        $this->validateMetadata($data, $metadata);

        $this->validateRegistrationIdInData($data, false);
    }

    public function validateOnUpdate($data)
    {
        $metadata = $this->metadata;
        $this->validateMetadata($data, $metadata);

        $this->validateRegistrationIdInData($data, true);
    }

    protected function validateRegistrationIdInData(array $data, $registrationIdRequired = true)
    {
        if ($registrationIdRequired && !isset($data['registrationId'])) {
            $errorList = new ErrorList();
            $errorList->addError('registrationId', 'Registration id is required for this action');
            $this->throwException($errorList);
        }

        if (isset($data['registrationId'])) {
            $this->validateRegistrationId($data['registrationId'], $registrationIdRequired);
        }
    }

    protected function validateRegistrationId($registrationId, $desired = true)
    {
        $errorList = new ErrorList();
        $errorList->setErrors('registrationId', $this->existenceValidator->validateRegistrationId($registrationId, $desired));
        $this->throwException($errorList);
    }
}