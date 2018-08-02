<?php

namespace Model\Proposal\ProposalFields;

class ProposalFieldChoice extends AbstractProposalField
{
    protected $value = array();

    public function addInformation(array &$variables)
    {
        $queryVariables1 = array_merge($variables, array("$this->name.value AS $this->name"));
        $queryVariables2 = array_merge($variables, array("collect($this->name) AS $this->name"));
        $variables[] = "$this->name";

        return " OPTIONAL MATCH (proposal)-[:INCLUDES]->($this->name:ProposalOption)"
            . " WITH " . implode(', ', $queryVariables1)
            . " WITH " . implode(', ', $queryVariables2);
    }

    public function getSaveQuery(array $variables)
    {
        $lines = array();

        foreach ($this->value AS $index => $optionValue)
        {
            $thisName = $this->name . $index;
            $lines[] = " MERGE (proposal)-[:INCLUDES]->($thisName:ProposalOption{value: '$optionValue'})";
        }

        $lines[] = " WITH " . implode(', ', $variables);

        return implode(' ', $lines);
    }
}