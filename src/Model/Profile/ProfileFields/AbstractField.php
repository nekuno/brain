<?php

namespace Model\Profile\ProfileFields;

abstract class AbstractField implements \JsonSerializable
{
    /** @var string */
    protected $nodeName;
    /** @var string */
    protected $name;
    /** @var string */
    protected $value;
    /** @var string */
    protected $type;

    /**
     * @return string
     */
    public abstract function queryAddInformation(array &$variables);

    /**
     * @return string
     */
    public abstract function getSaveQuery(array $variables);


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }

    /**
     * @param mixed $nodeName
     */
    public function setNodeName($nodeName): void
    {
        $this->nodeName = $nodeName;
    }

    public function jsonSerialize()
    {
        return $this->value;
    }

}