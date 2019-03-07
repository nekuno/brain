<?php

namespace Model\Profile\ProfileFields;

class FieldString extends AbstractField
{
    public function queryAddInformation(array &$variables)
    {
        $queryVariables = array_merge($variables, array("$this->nodeName.$this->name AS $this->name"));
        $variables[] = "$this->name";

        return 'WITH ' . implode(', ', $queryVariables);
    }

    public function getSaveQuery(array $variables)
    {
        $value = str_replace("'", "\'", $this->value);
        return "SET $this->nodeName.$this->name = '$value'"
            . ' WITH ' . implode(', ', $variables);
    }
}