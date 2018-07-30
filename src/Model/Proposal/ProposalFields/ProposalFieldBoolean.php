<?php

namespace Model\Proposal\ProposalFields;

class ProposalFieldBoolean extends AbstractProposalField
{
    public function addInformation(array &$variables)
    {
        $queryVariables = array_merge($variables, array("proposal.$this->name AS $this->name"));
        $variables[] = "$this->name";

        return 'WITH ' . implode(', ', $queryVariables);
    }

    public function getSaveQuery(array $variables)
    {
        return "SET proposal.$this->name = $this->value"
            . 'WITH' . implode(', ', $variables);
    }

}