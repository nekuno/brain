<?php

namespace Model\Profile\ProfileFields;

class FieldChoice extends AbstractField
{
    protected $value = array();

    public function queryAddInformation(array &$variables)
    {
        $queryVariables1 = array_merge($variables, array("{value: $this->name.id, image: $this->name.image} AS $this->name"));
        $queryVariables2 = array_merge($variables, array("collect($this->name) AS $this->name"));
        $variables[] = "$this->name";

        return " OPTIONAL MATCH ($this->nodeName)-[:INCLUDES]->($this->name:ProfileOption)"
            . " WITH " . implode(', ', $queryVariables1)
            . " WITH " . implode(', ', $queryVariables2);
    }

    public function getSaveQuery(array $variables)
    {
        $lines = array();

        foreach ($this->value AS $index => $optionValue)
        {
            $thisName = $this->name . $index;
            $lines[] = " MATCH ($thisName:ProfileOption{id: '$optionValue'})";
            $lines[] = " MERGE ($this->nodeName)-[:INCLUDES]->($thisName)";
            $lines[] = " WITH " . implode(', ', $variables);
        }

        $lines[] = " WITH " . implode(', ', $variables);

        return implode(' ', $lines);
    }
}