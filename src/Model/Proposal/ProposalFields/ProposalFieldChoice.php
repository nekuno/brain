<?php

namespace Model\Proposal\ProposalFields;

class ProposalFieldChoice extends AbstractProposalField
{
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
}