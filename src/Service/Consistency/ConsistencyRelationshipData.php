<?php

namespace Service\Consistency;

class ConsistencyRelationshipData
{
    protected $id;
    protected $type;
    protected $properties = array();
    protected $startNodeId;
    protected $startNodeLabels = array();
    protected $endNodeId;
    protected $endNodeLabels = array();

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    /**
     * @return mixed
     */
    public function getStartNodeId()
    {
        return $this->startNodeId;
    }

    /**
     * @param mixed $startNodeId
     */
    public function setStartNodeId($startNodeId)
    {
        $this->startNodeId = $startNodeId;
    }

    /**
     * @return array
     */
    public function getStartNodeLabels()
    {
        return $this->startNodeLabels;
    }

    /**
     * @param mixed $startNodeLabels
     */
    public function setStartNodeLabels($startNodeLabels)
    {
        $this->startNodeLabels = $startNodeLabels;
    }

    /**
     * @return mixed
     */
    public function getEndNodeId()
    {
        return $this->endNodeId;
    }

    /**
     * @param mixed $endNodeId
     */
    public function setEndNodeId($endNodeId)
    {
        $this->endNodeId = $endNodeId;
    }

    /**
     * @return array
     */
    public function getEndNodeLabels()
    {
        return $this->endNodeLabels;
    }

    /**
     * @param mixed $endNodeLabels
     */
    public function setEndNodeLabels($endNodeLabels)
    {
        $this->endNodeLabels = $endNodeLabels;
    }

}