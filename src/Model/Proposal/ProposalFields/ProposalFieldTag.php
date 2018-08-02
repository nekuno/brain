<?php

namespace Model\Proposal\ProposalFields;

class ProposalFieldTag extends AbstractProposalField
{
    //TODO: Use ProposalTag--TextLanguage
    public function addInformation(array &$variables)
    {
        $tagLabel = $this->getTagLabel();
        $queryVariables = array_merge($variables, array("text$this->name.canonical AS $this->name"));
        $variables[] = "$this->name";

        return "OPTIONAL MATCH (proposal)-[:INCLUDES]->($this->name:ProposalTag:$tagLabel)<-[:TEXT_OF]-(text$this->name:TextLanguage{locale: {locale}})"
            . "WITH " . implode(', ', $queryVariables);
    }

    //TODO: Use ProposalTag--TextLanguage
    public function getSaveQuery(array $variables)
    {
        $tagLabel = $this->getTagLabel();
        return "MERGE ($this->name:ProposalTag:$tagLabel)<-[:TEXT_OF]-(text$this->name:TextLanguage{locale: {locale}, canonical: '$this->value'}) MERGE (proposal)-[:INCLUDES]->($this->name) "
            . "WITH " . implode(', ', $variables);
    }

    protected function getTagLabel()
    {
        return $this->name.'Tag';
    }
}