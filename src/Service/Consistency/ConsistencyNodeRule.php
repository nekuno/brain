<?php

namespace Service\Consistency;


class ConsistencyNodeRule
{
    protected $label;
    protected $properties;
    protected $relationships;
    protected $checkerClass;
    protected $solverClass;

    /**
     * ConsistencyRelationshipRule constructor.
     * @param array $rule
     */
    public function __construct(array $rule)
    {
        $this->label = isset($rule['label']) ? $rule['label'] : null;
        $this->properties = isset($rule['properties']) ? $rule['properties'] : array();
        $this->relationships = isset($rule['relationships']) ? $rule['relationships'] : array();
        $this->checkerClass = isset($rule['checkerClass']) ? $rule['checkerClass'] : null;
        $this->solverClass = isset($rule['solverClass']) ? $rule['solverClass'] : null;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
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

    /**
     * @return mixed
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * @param mixed $relationships
     */
    public function setRelationships($relationships)
    {
        $this->relationships = $relationships;
    }

    /**
     * @return mixed
     */
    public function getCheckerClass()
    {
        return $this->checkerClass;
    }

    /**
     * @param mixed $checkerClass
     */
    public function setCheckerClass($checkerClass)
    {
        $this->checkerClass = $checkerClass;
    }

    /**
     * @return mixed
     */
    public function getSolverClass()
    {
        return $this->solverClass;
    }

    /**
     * @param mixed $solverClass
     */
    public function setSolverClass($solverClass)
    {
        $this->solverClass = $solverClass;
    }

}