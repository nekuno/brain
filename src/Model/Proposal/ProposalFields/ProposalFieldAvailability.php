<?php

namespace Model\Proposal\ProposalFields;

use Model\Availability\Availability;

class ProposalFieldAvailability implements ProposalFieldInterface
{
    /**
     * @var Availability
     */
    protected $availability;

    public function addInformation(array &$variables)
    {
        $variables[] = 'availabilityId';

        $queryVariables = array_merge($variables, "id(availability) AS availabilityId");

        return 'OPTIONAL MATCH (proposal)-[:HAS_AVAILABILITY]->(availability:Availability)'
            . "WITH " . implode(', ', $queryVariables);

    }

    public function getSaveQuery(array $variables)
    {
        $availabilityId = $this->availability->getId();

        return "OPTIONAL MATCH (availability) WHERE id(availability) = $availabilityId "
            . "MERGE (proposal)-[:HAS_AVAILABILITY]->(availability)";
    }

    public function getData()
    {
        return array('availability' => $this->availability);
    }

    public function getName()
    {
        return 'availability';
    }

    public function setName($name)
    {
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
}