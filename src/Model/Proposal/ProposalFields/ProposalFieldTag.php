<?php

namespace Model\Proposal\ProposalFields;

class ProposalFieldTag extends AbstractProposalField
{
    protected $value = array();

    public function addInformation(array &$variables)
    {
        $tagLabel = $this->getTagLabel();
        $queryVariables1 = array_merge($variables, array("text$this->name.canonical AS $this->name"));
        $queryVariables2 = array_merge($variables, array("collect($this->name) AS $this->name"));
        $variables[] = "$this->name";

        return "OPTIONAL MATCH (proposal)-[:INCLUDES]->($this->name:ProposalTag:$tagLabel)<-[:TEXT_OF]-(text$this->name:TextLanguage{locale: {locale}})"
            . " WITH " . implode(', ', $queryVariables1)
            . " WITH " . implode(', ', $queryVariables2);
    }

    public function getSaveQuery(array $variables)
    {
        $lines = array();

        $tagLabel = $this->getTagLabel();
        foreach ($this->value AS $index => $tagValue)
        {
            $thisName = $this->name . $index;
            $lines[] = " MERGE ($thisName:ProposalTag:$tagLabel)<-[:TEXT_OF]-(:TextLanguage{locale: {locale}, canonical: '$tagValue'})"
                . " MERGE (proposal)-[:INCLUDES]->($thisName)";
        }

        $lines[] = " WITH " . implode(', ', $variables);

        return implode(' ', $lines);
    }

    protected function getTagLabel()
    {
        return $this->name.'Tag';
    }
}