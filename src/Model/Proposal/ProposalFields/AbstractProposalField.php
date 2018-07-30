<?php

namespace Model\Proposal\ProposalFields;

abstract class AbstractProposalField
{
    protected $name;
    protected $value;
    protected $type;
    /**
     * Add new variable to $variables to be used with WITH later
     * Return partial query to add
     * @param array $variables Already available variables from the earlier query, ready to be used with WITH
     * @return mixed
     */
    public abstract function addInformation(array &$variables);

    public abstract function getSaveQuery(array $variables);


    /**
     * @return mixed
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
     * @return mixed
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
     * @return mixed
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


}