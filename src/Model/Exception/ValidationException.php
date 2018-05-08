<?php

namespace Model\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;


class ValidationException extends HttpException
{
    protected $errorList;

    public function __construct(ErrorList $errorList, $message = 'Validation error', \Exception $previous = null, array $headers = array(), $code = 0)
    {
        // https://tools.ietf.org/html/rfc4918#page-78
        $this->errorList = $errorList;
        parent::__construct(422, $message, $previous, $headers, $code);
    }

    /**
     * Get any validation errors
     *
     * @return array
     */
    public function getErrors()
    {
        if (null == $this->errorList)
        {
            return array();
        }

        return $this->errorList->jsonSerialize();
    }

}