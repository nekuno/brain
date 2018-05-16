<?php

namespace Model\Matching;

class Matching implements \JsonSerializable
{
    protected $matching = 0;

    protected $timestamp = 0;

    /**
     * @return mixed
     */
    public function getMatching()
    {
        return $this->matching;
    }

    /**
     * @param mixed $matching
     */
    public function setMatching($matching)
    {
        $this->matching = $matching;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param mixed $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function jsonSerialize()
    {
        return array(
            'matching' => $this->matching,
        );
    }

}