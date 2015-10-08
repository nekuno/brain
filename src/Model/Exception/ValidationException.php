<?php

namespace Model\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class ValidationException extends HttpException
{
    protected $errors = array();

    public function __construct($message = null, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        // https://tools.ietf.org/html/rfc4918#page-78
        parent::__construct(422, $message, $previous, $headers, $code);
    }

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