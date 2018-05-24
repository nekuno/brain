<?php

namespace Service\Consistency;

class ConsistencyNodeData
{
    protected $id;
    protected $labels = array();
    protected $properties = array();
    protected $incoming = array();
    protected $outgoing = array();

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
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param mixed $labels
     */
    public function setLabels($labels)
    {
        $this->labels = $labels;
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

    public function addProperty($key, $value)
    {
        $this->properties[$key] = $value;
    }

    /**
     * @return ConsistencyRelationshipData[]
     */
    public function getIncoming()
    {
        return $this->incoming;
    }

    /**
     * @param array $incoming
     */
    public function setIncoming($incoming)
    {
        $this->incoming = $incoming;
    }

    /**
     * @return ConsistencyRelationshipData[]
     */
    public function getOutgoing()
    {
        return $this->outgoing;
    }

    /**
     * @param array $outgoing
     */
    public function setOutgoing($outgoing)
    {
        $this->outgoing = $outgoing;
    }

}