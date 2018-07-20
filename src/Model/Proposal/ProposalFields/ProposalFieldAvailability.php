<?php

namespace Model\Proposal\ProposalFields;

use Model\Availability\Availability;

class ProposalFieldAvailability
{
    /**
     * @var Availability
     */
    protected $availability;

    public function getAvailability()
    {
        return $this->availability;
    }

    public function setAvailability(Availability $availability): void
    {
        $this->availability = $availability;
    }


}