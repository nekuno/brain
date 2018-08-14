<?php

namespace Model\Recommendation;

use Model\Proposal\Proposal;

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