<?php

namespace Service\Consistency;


class ConsistencyRelationshipRule
{
    protected $type;
    protected $direction;
    protected $otherNode;
    protected $minimum;
    protected $maximum;
    protected $properties;

    /**
     * ConsistencyRelationshipRule constructor.
     * @param array $rule
     */
    public function __construct(array $rule)
    {
        $this->type = isset($rule['type']) ? $rule['type'] : null;
        $this->direction = isset($rule['direction']) ? $rule['direction'] : null;
        $this->otherNode = isset($rule['otherNode']) ? $rule['otherNode'] : null;
        $this->minimum = isset($rule['minimum']) ? $rule['minimum'] : 0;
        $this->maximum = isset($rule['maximum']) ? $rule['maximum'] : 999999999;
        $this->properties = isset($rule['properties']) ? $rule['properties'] : array();
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
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * @param mixed|null $direction
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
    }

    /**
     * @return mixed|null
     */
    public function getOtherNode()
    {
        return $this->otherNode;
    }

    /**
     * @param mixed|null $otherNode
     */
    public function setOtherNode($otherNode)
    {
        $this->otherNode = $otherNode;
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
     * @return mixed|null
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param mixed|null $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

}