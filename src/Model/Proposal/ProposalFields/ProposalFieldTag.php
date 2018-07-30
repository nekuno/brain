<?php

namespace Model\Proposal\ProposalFields;

class ProposalFieldTag extends AbstractProposalField
{
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
}