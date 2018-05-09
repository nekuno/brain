<?php

namespace Service\Validator;

use Model\Exception\ErrorList;

class InvitationValidator extends Validator
{
    public function validateOnCreate($data)
    {
        if (isset($data['token'])) {
            $this->validateInvitationToken($data['token'], null, false);
        }

        $this->validateGroupInData($data, false);
        $this->validateUserInData($data, false);

        $metadata = $this->metadata;
        $this->validateMetadata($data, $metadata);
    }

    public function validateOnUpdate($data)
    {
        $metadata = $this->metadata;
        $metadata['invitationId']['required'] = true;

        $this->validateGroupInData($data, false);
        $this->validateUserInData($data, false);

        $this->validateMetadata($data, $metadata);

        if (isset($data['invitationId'])) {
            $this->validateInvitationId($data['invitationId'], true);
        }

        if (isset($data['token'])) {
            $this->validateInvitationToken($data['token'], $data['invitationId'], false);
        }
    }

    public function validateOnDelete($data)
    {
        $this->validateGroupInData($data);
        $this->validateUserInData($data);
    }

    protected function validateInvitationId($invitationId, $desired = true)
    {
        $errorList = new ErrorList();
        $errorList->setErrors('invitationId', $this->existenceValidator->validateInvitationId($invitationId, $desired));
        $this->throwException($errorList);
    }

    protected function validateInvitationToken($token, $excludedId = null, $desired = true)
    {
        $errorList = new ErrorList();

        if (!is_string($token) && !is_numeric($token)) {
            $errorList->addError('token', 'Token must be a string or a numeric');
            $this->throwException($errorList);
        }

        $errorList->setErrors('invitationToken', $this->existenceValidator->validateInvitationToken($token, $excludedId, $desired));
        $this->throwException($errorList);
    }

    protected function validateGroupId($groupId, $desired = true)
    {
        $errorList = new ErrorList();
        if (!is_int($groupId)) {
            $errorList->addError('groupId', 'Group Id must be an integer');
        } else {
            $errorList->setErrors('groupId', $this->existenceValidator->validateGroupId($groupId, $desired));
        }

        $this->throwException($errorList);
    }

    protected function validateGroupInData(array $data, $groupIdRequired = true)
    {
        if ($groupIdRequired && !isset($data['groupId'])) {
            $errorList = new ErrorList();
            $errorList->addError('groupId', 'Group id is required for this action');
            $this->throwException($errorList);
        }

        if (isset($data['groupId'])) {
            $this->validateGroupId($data['groupId']);
        }
    }
}