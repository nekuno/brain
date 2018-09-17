<?php

namespace Model\Recommendation\Proposal;

use Model\Proposal\Proposal;
use Model\Recommendation\AbstractUserRecommendation;

class ProposalCandidateRecommendation extends AbstractUserRecommendation
{
    /**
     * @var Proposal
     */
    protected $proposal;

    /**
     * @return Proposal
     */
    public function getProposal(): Proposal
    {
        return $this->proposal;
    }

    /**
     * @param Proposal $proposal
     */
    public function setProposal(Proposal $proposal): void
    {
        $this->proposal = $proposal;
    }

    function jsonSerialize()
    {
        $array = parent::jsonSerialize();

        $array['proposal'] = $this->proposal->getName();

        return $array;
    }

}