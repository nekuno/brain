<?php

namespace Service\Consistency;


class ConsistencyPropertyRule
{
    protected $required;
    protected $type;
    protected $minimum;
    protected $maximum;
    protected $options;
    protected $key;

    const TYPE_INTEGER = 'integer';
    const TYPE_DATETIME = 'datetime';
    const TYPE_ARRAY = 'array';
    const TYPE_BOOLEAN = 'boolean';

    /**
     * ConsistencyRelationshipRule constructor.
     * @param null $key
     * @param array $rule
     */
    public function __construct($key = null, array $rule)
    {
        $this->required = isset($rule['required']) ? $rule['required'] : false;
        $this->type = isset($rule['type']) ? $rule['type'] : null;
        $this->key = $key;
        $this->minimum = isset($rule['minimum']) ? $rule['minimum'] : null;
        $this->maximum = isset($rule['maximum']) ? $rule['maximum'] : null;
        $this->options = isset($rule['options']) ? $rule['options'] : array();
    }

    /**
     * @return bool|mixed
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param bool|mixed $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @return mixed|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed|null $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed|null
     */
    public function getMinimum()
    {
        return $this->minimum;
    }

    /**
     * @param mixed|null $minimum
     */
    public function setMinimum($minimum)
    {
        $this->minimum = $minimum;
    }

    /**
     * @return mixed|null
     */
    public function getMaximum()
    {
        return $this->maximum;
    }

    /**
     * @param mixed|null $maximum
     */
    public function setMaximum($maximum)
    {
        $this->maximum = $maximum;
    }

    /**
     * @return array|mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array|mixed $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }
}