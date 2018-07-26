<?php

namespace Model\Proposal\ProposalFields;

class ProposalFieldChoice implements ProposalFieldInterface
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
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    //TODO: Use ProposalOption--TextLanguage
    public function addInformation(array &$variables)
    {
        $queryVariables = array_merge($variables, array("$this->name.value AS $this->name"));
        $variables[] = "$this->name";

        return "OPTIONAL MATCH (proposal)-[:INCLUDES]->($this->name:ProposalOption)" . "WITH " . implode(', ', $queryVariables);
    }

    //TODO: Use ProposalOption--TextLanguage
    public function getSaveQuery(array $variables)
    {
        return "MERGE (proposal)-[:INCLUDES]->($this->name:ProposalOption{value: '$this->value'}) " . "WITH " . implode(', ', $variables);
    }

    public function getData()
    {
        return array ($this->name => $this->value);
    }
}