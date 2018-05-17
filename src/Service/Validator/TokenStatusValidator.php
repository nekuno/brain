<?php

namespace Service\Validator;

class TokenStatusValidator extends Validator
{
    public function validateOnCreate($data)
    {
        $metadata = array(
            'boolean' => array(
                'type' => 'integer',
                'min' => 0,
                'max' => 1
            )
        );

        return $this->validateMetadata($data, $metadata);
    }

}