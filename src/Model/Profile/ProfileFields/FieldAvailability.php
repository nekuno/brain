<?php

namespace Model\Profile\ProfileFields;

use Model\Availability\Availability;

class FieldAvailability extends AbstractField
{
    /**
     * @var Availability
     */
    protected $availability;

    public function queryAddInformation(array &$variables)
    {
        $queryVariables = array_merge($variables, array("id(availability) AS availabilityId"));
        $variables[] = 'availabilityId';

        return 'OPTIONAL MATCH (' . $this->nodeName . ')-[:HAS_AVAILABILITY]->(availability:Availability)'
            . " WITH " . implode(', ', $queryVariables);

    }

    public function getSaveQuery(array $variables)
    {
        return '';
    }

    /**
     * @return Availability
     */
    public function getAvailability(): Availability
    {
        return $this->availability;
    }

    /**
     * @param Availability $availability
     */
    public function setAvailability(Availability $availability): void
    {
        $this->availability = $availability;
    }

    public function jsonSerialize()
    {
        return $this->availability;
    }
}