<?php

namespace Event;

class MatchingProcessStepEvent extends MatchingProcessEvent
{

    protected $percentage;

    public function __construct($userId, $processId, $percentage)
    {
        parent::__construct($userId, $processId);
        $this->percentage = (integer)$percentage;
    }

    public function getPercentage()
    {
        return $this->percentage;
    }
}
