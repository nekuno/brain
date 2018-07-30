<?php

namespace Model\Proposal\ProposalFields;

use Model\Availability\Availability;

class ProposalFieldAvailability extends AbstractProposalField
{
    /**
     * @var Availability
     */
    protected $availability;

    public function addInformation(array &$variables)
    {
        $queryVariables = array_merge($variables, array("id(availability) AS availabilityId"));
        $variables[] = 'availabilityId';

        return 'OPTIONAL MATCH (proposal)-[:HAS_AVAILABILITY]->(availability:Availability)'
            . " WITH " . implode(', ', $queryVariables);

    }

    public function getSaveQuery(array $variables)
    {
        $availabilityId = $this->availability->getId();

        return "OPTIONAL MATCH (availability) WHERE id(availability) = $availabilityId "
            . " MERGE (proposal)-[:HAS_AVAILABILITY]->(availability)";
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