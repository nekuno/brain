<?php

namespace Model\Proposal\ProposalFields;

class ProposalFieldTag implements ProposalFieldInterface
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

    //TODO: Use ProposalTag--TextLanguage
    public function addInformation(array &$variables)
    {
        $variables[] = "tag$this->name";

        $queryVariables = array_merge($variables, "tag.value AS tag$this->name");
        return "OPTIONAL MATCH (proposal)-[:INCLUDES]->(tag$this->name:ProposalTag)" . "WITH " . implode(', ', $queryVariables);
    }

    //TODO: Use ProposalTag--TextLanguage
    public function getSaveQuery(array $variables)
    {
        return "MERGE (tag$this->name:ProposalTag{value: $this->value}) MERGE (proposal)-[:INCLUDES]->(tag$this->name) " . "WITH " . implode(', ', $variables);
    }

    public function getData()
    {
        return array ($this->name => $this->value);
    }
}