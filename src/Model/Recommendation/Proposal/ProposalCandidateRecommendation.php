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
     * @var bool
     */
    protected $interested = false;

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

    /**
     * @return bool
     */
    public function isInterested(): bool
    {
        return $this->interested;
    }

    /**
     * @param bool $interested
     */
    public function setInterested(bool $interested): void
    {
        $this->interested = $interested;
    }

    function jsonSerialize()
    {
        $array = parent::jsonSerialize();

        $array['proposalId'] = $this->proposal->getId();

        return $array;
    }

}