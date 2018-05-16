<?php

namespace Model\Content;

class ComparedInterest extends Interest
{
    protected $match;

    public function jsonSerialize()
    {
        $interest = parent::jsonSerialize();

        $interest['match'] = $this->getMatch();

        return $interest;
    }

    /**
     * @return mixed
     */
    public function getMatch()
    {
        return $this->match;
    }

    /**
     * @param mixed $match
     */
    public function setMatch($match)
    {
        $this->match = $match;
    }
}