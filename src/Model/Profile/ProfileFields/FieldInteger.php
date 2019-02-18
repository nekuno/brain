<?php

namespace Model\Profile\ProfileFields;

class FieldInteger extends AbstractField
{
    protected $max = 99999999;
    protected $min = 0;

    /**
     * @return int
     */
    public function getMax(): int
    {
        return $this->max;
    }

    /**
     * @param int $max
     */
    public function setMax(int $max): void
    {
        $this->max = $max;
    }

    /**
     * @return int
     */
    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * @param int $min
     */
    public function setMin(int $min): void
    {
        $this->min = $min;
    }

    public function queryAddInformation(array &$variables)
    {
        $queryVariables = array_merge($variables, array("$this->nodeName.$this->name AS $this->name"));
        $variables[] = "$this->name";

        return 'WITH ' . implode(', ', $queryVariables);
    }

    public function getSaveQuery(array $variables)
    {
        return "SET $this->nodeName.$this->name = $this->value"
            . ' WITH ' . implode(', ', $variables);
    }
}