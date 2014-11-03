<?php

namespace Model\Exception;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class ValidationException extends \RuntimeException
{
    protected $errors = array();

    /**
     * Get any validation errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set the validation error messages
     *
     * @param array $errors Array of validation errors
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }
}