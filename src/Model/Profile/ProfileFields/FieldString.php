<?php

namespace Model\Profile\ProfileFields;

class FieldString extends AbstractField
{
    public function addInformation(array &$variables)
    {
        $queryVariables = array_merge($variables, array("$this->nodeName.$this->name AS $this->name"));
        $variables[] = "$this->name";

        return 'WITH ' . implode(', ', $queryVariables);
    }

    public function getSaveQuery(array $variables)
    {
        return "SET $this->nodeName.$this->name = '$this->value'"
            . ' WITH ' . implode(', ', $variables);
    }
}