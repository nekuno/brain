<?php

namespace Model\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;


class ValidationException extends HttpException
{
    protected $errors = array();

    public function __construct(array $errors, $message = 'Validation error', \Exception $previous = null, array $headers = array(), $code = 0)
    {
        // https://tools.ietf.org/html/rfc4918#page-78
        $this->errors = $errors;
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

}