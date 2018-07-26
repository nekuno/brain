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

    //TODO: Use ProposalTag--TextLanguage
    public function addInformation(array &$variables)
    {
        $tagLabel = $this->name.'Tag';
        $queryVariables = array_merge($variables, array("$this->name.value AS $this->name"));
        $variables[] = "$this->name";

        return "OPTIONAL MATCH (proposal)-[:INCLUDES]->($this->name:ProposalTag:$tagLabel)"
            . "WITH " . implode(', ', $queryVariables);
    }

    //TODO: Use ProposalTag--TextLanguage
    public function getSaveQuery(array $variables)
    {
        $tagLabel = $this->name.'Tag';
        return "MERGE ($this->name:ProposalTag:$tagLabel{value: '$this->value'}) MERGE (proposal)-[:INCLUDES]->($this->name) "
            . "WITH " . implode(', ', $variables);
    }

    public function getData()
    {
        return array ($this->name => $this->value);
    }
}