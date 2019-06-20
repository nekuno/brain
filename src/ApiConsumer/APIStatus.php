<?php

namespace ApiConsumer;

class APIStatus
{
    protected $percentageUsed;

    protected $timeChecked;

    /**
     * @return mixed
     */
    public function getPercentageUsed()
    {
        return $this->percentageUsed;
    }

    /**
     * @param mixed $percentageUsed
     */
    public function setPercentageUsed($percentageUsed): void
    {
        $this->percentageUsed = $percentageUsed;
    }

    /**
     * @return mixed
     */
    public function getTimeChecked()
    {
        return $this->timeChecked;
    }

    /**
     * @param mixed $timeChecked
     */
    public function setTimeChecked($timeChecked): void
    {
        $this->timeChecked = $timeChecked;
    }
}