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

    protected $aboutMe = '';

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

    /**
     * @return string
     */
    public function getAboutMe(): string
    {
        return $this->aboutMe;
    }

    /**
     * @param string $aboutMe
     */
    public function setAboutMe(string $aboutMe): void
    {
        $this->aboutMe = $aboutMe;
    }

    function jsonSerialize()
    {
        $array = parent::jsonSerialize();

        $array['proposal'] = $this->proposal;
        $array['aboutMe'] = $this->aboutMe;

        return $array;
    }

}