<?php

namespace Service\Validator;

use Model\Exception\ErrorList;

class GroupValidator extends Validator
{
    public function validateOnCreate($data)
    {
        return $this->validate($data);
    }

    public function validateOnUpdate($data)
    {
        $this->validateGroupInData($data);
        return $this->validate($data);
    }

    public function validateOnDelete($data)
    {
        $this->validateGroupInData($data);
        $this->validateUserInData($data);
    }

    public function validateOnAddUser($data)
    {
        $this->validateGroupInData($data);
        $this->validateUserInData($data);
    }

    protected function validate(array $data)
    {
        $metadata = $this->metadata;
        $this->validateMetadata($data, $metadata);

        $errorList = new ErrorList();
        if (isset($data['followers']) && $data['followers']) {
            $errorList->setErrors('followers', $this->validateBoolean($data['followers']));
            if (!isset($data['influencer_id'])) {
                $errorList->addError('influencer_id', '"influencer_id" is required for followers groups');
            } elseif (!is_int($data['influencer_id'])) {
                $errorList->addError('influencer_id', '"influencer_id" must be integer');
            }
            if (!isset($data['min_matching'])) {
                $errorList->addError('min_matching', '"min_matching" is required for followers groups');
            } elseif (!is_int($data['min_matching'])) {
                $errorList->addError('min_matching', '"min_matching" must be integer');
            }
            if (!isset($data['type_matching'])) {
                $errorList->addError('type_matching', '"type_matching" is required for followers groups');
            } elseif ($data['type_matching'] !== 'similarity' && $data['type_matching'] !== 'compatibility') {
                $errorList->addError('type_matching', '"type_matching" must be "similarity" or "compatibility"');
            }
        }

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