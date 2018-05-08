<?php

namespace Model\Exception;

class ErrorList implements \JsonSerializable
{
    protected $errors = array();

    public function addError($field, $error)
    {
        if (empty($error)){
            return;
        }

        $this->errors[$field][] = $error;
    }

    public function setErrors($field, array $errors)
    {
        $this->errors[$field] = $errors;
    }

    public function hasErrors()
    {
        $this->filterEmptyErrors();

        return count($this->errors) > 0;
    }

    public function hasFieldErrors($field)
    {
        $this->filterEmptyErrors();

        return isset($this->errors[$field]);
    }

    public function jsonSerialize()
    {
        $this->filterEmptyErrors();

        return $this->errors;
    }

    protected function filterEmptyErrors()
    {
        foreach ($this->errors as $field => &$fieldErrors)
        {
            foreach ($fieldErrors as $index => $fieldError)
            {
                if (empty($fieldError)){
                    unset($fieldErrors[$index]);
                }
            }

            if (empty($fieldErrors))
            {
                unset($this->errors[$field]);
            }
        }
    }

}