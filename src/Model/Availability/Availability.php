<?php

namespace Model\Availability;

class Availability
{
    protected $id;

    protected $dates = array();

    protected $daysIds = array();

    //TODO: Dynamic ranges
    /**
     * @return mixed
     */
    public function getDates()
    {
        return $this->dates;
    }

    /**
     * @param mixed $dates
     */
    public function setDates($dates): void
    {
        $this->dates = $dates;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getDaysIds()
    {
        return $this->daysIds;
    }

    /**
     * @param mixed $daysIds
     */
    public function setDaysIds($daysIds): void
    {
        $this->daysIds = $daysIds;
    }

}