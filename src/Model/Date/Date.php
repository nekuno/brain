<?php

namespace App\Model\Date;

class Date implements \JsonSerializable
{
    protected $maxYear = 2020;
    protected $minYear = -2017;

    protected $day;
    protected $month;
    protected $year;

    protected $dayId;

    /**
     * @param $string yyyy-mm-dd
     */
    public function __construct($string = null)
    {
        if ($string == null){
            return;
        }

        $this->year = $this->extractYear($string);
        $this->month = $this->extractMonth($string);
        $this->day = $this->extractDay($string);

        $this->validate();
    }

    protected function validate()
    {
        $yearCorrect = is_int($this->year) && $this->year < $this->maxYear && $this->year > $this->minYear;
        $monthCorrect = is_int($this->month) && $this->month > 0 && $this->month <= 12;
        $dayCorrect = is_int($this->day) && $this->day > 0 && $this->day <= 31;

        if (!$yearCorrect || !$monthCorrect || !$dayCorrect){
            throw new \Exception('Date format not valid');
        }
    }

    protected function extractYear($string)
    {
        return (int)substr($string, 0, 4);
    }

    protected function extractMonth($string)
    {
        return (int)substr($string, 5, 2);
    }

    protected function extractDay($string)
    {
        return (int)substr($string, 8, 2);
    }

    /**
     * @return mixed
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * @param mixed $day
     */
    public function setDay($day): void
    {
        $this->day = $day;
    }

    /**
     * @return mixed
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @param mixed $month
     */
    public function setMonth($month): void
    {
        $this->month = $month;
    }

    /**
     * @return mixed
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param mixed $year
     */
    public function setYear($year): void
    {
        $this->year = $year;
    }

    /**
     * @return mixed
     */
    public function getDayId()
    {
        return $this->dayId;
    }

    /**
     * @param mixed $dayId
     */
    public function setDayId($dayId): void
    {
        $this->dayId = $dayId;
    }

    public function jsonSerialize()
    {
        return $this->year . '-' . $this->month . '-' . $this->day;
    }

}