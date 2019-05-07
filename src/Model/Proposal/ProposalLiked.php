<?php

namespace Model\Proposal;

use Model\Profile\OtherProfileData;

class ProposalLiked
{
    /** @var Proposal */
    protected $proposal;
    /** @var bool */
    protected $hasMatch;
    /** @var OtherProfileData */
    protected $owner;

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
    public function isHasMatch(): bool
    {
        return $this->hasMatch;
    }

    /**
     * @param bool $hasMatch
     */
    public function setHasMatch(bool $hasMatch): void
    {
        $this->hasMatch = $hasMatch;
    }

    /**
     * @return OtherProfileData
     */
    public function getOwner(): OtherProfileData
    {
        return $this->owner;
    }

    /**
     * @param OtherProfileData $owner
     */
    public function setOwner(OtherProfileData $owner): void
    {
        $this->owner = $owner;
    }
}