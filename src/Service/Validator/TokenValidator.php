<?php

namespace Service\Validator;

use Model\Exception\ErrorList;
use Model\Token\TokensManager;

class TokenValidator extends Validator
{
    public function validateOnCreate($data)
    {
        $metadata = $this->metadata;
        $metadata['resourceId']['required'] = true;
        if (isset($data['resourceOwner']) && $data['resourceOwner'] === TokensManager::STEAM) {
            $metadata['oauthToken']['required'] = false;
        }
        $choices = $this->getChoices();

        $this->validateMetadata($data, $metadata, $choices);

        $this->validateUserInData($data, false);
        $this->validateTokenResourceId($data, false);

//        $this->validateExtraFields($data, $metadata);
    }

    public function validateOnUpdate($data)
    {
        $metadata = $this->metadata;
        $choices = $this->getChoices();

        $this->validateMetadata($data, $metadata, $choices);

        unset($data['userId']);
        $this->validateTokenResourceId($data, true);

//        $this->validateExtraFields($data, $metadata);
    }

    public function validateOnDelete($data)
    {
        $metadata = $this->metadata;
        $metadata['oauthToken']['required'] = false;
        $metadata['resourceId']['required'] = false;
        $choices = $this->getChoices();

        $this->validateMetadata($data, $metadata, $choices);

        $this->validateUserInData($data, true);
        $this->validateTokenResourceId($data, true);
    }

    private function getChoices()
    {
        return array(
            'resourceOwner' => TokensManager::getResourceOwners(),
        );
    }

    protected function validateTokenResourceId($data, $desired = true)
    {
        $userId = isset($data['userId']) ? $data['userId'] : null;
        $resourceOwner = $data['resourceOwner'];
        $resourceId = $data['resourceId'];

        $errorList = new ErrorList();
        $errorList->setErrors('tokenResourceId', $this->existenceValidator->validateTokenResourceId($resourceId, $userId, $resourceOwner, $desired));

        $this->throwException($errorList);
    }
}