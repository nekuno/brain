<?php

namespace Model\Profile\ProfileFields;

class FieldTag extends AbstractField
{
    protected $value = array();

    public function queryAddInformation(array &$variables)
    {
        $tagLabel = $this->getTagLabel();
        $queryVariables1 = array_merge($variables, array("text$this->name.canonical AS $this->name"));
        $queryVariables2 = array_merge($variables, array("collect($this->name) AS $this->name"));
        $variables[] = "$this->name";

        return "OPTIONAL MATCH ($this->nodeName)-[:INCLUDES]->($this->name:ProfileTag:$tagLabel)<-[:TEXT_OF]-(text$this->name:TextLanguage{locale: {locale}})"
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
            $lines[] = " MERGE ($thisName:ProfileTag:$tagLabel)<-[:TEXT_OF]-(:TextLanguage{locale: {locale}, canonical: '$tagValue'})"
                . " MERGE ($this->nodeName)-[:INCLUDES]->($thisName)";
        }

        $lines[] = " WITH " . implode(', ', $variables);

        return implode(' ', $lines);
    }

    protected function getTagLabel()
    {
        return $this->name.'Tag';
    }
}