<?php

namespace App\Model\Proposal\ProposalFields;

use Model\Proposal\ProposalFields\ProposalFieldInterface;

class ProposalFieldBoolean implements ProposalFieldInterface
{
    protected $name;

    protected $value;

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function addInformation(array &$variables)
    {
        $queryVariables = array_merge($variables, array("proposal.$this->name AS $this->name"));
        $variables[] = "$this->name";

        return 'WITH ' . implode(', ', $queryVariables);
    }

    public function getSaveQuery(array $variables)
    {
        return "SET proposal.$this->name = $this->value"
            . 'WITH' . implode(', ', $variables);    }

    public function getData()
    {
        return array ($this->name => $this->value);
    }

    public function getName()
    {
        return $this->name;
    }

}