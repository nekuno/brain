<?php

namespace Model\Availability;

use Model\Date\Date;
use Model\Date\DateManager;
use Model\Date\DayPeriodManager;

class AvailabilityDataFormatter
{
    protected $dateManager;
    protected $dayPeriodManager;

    /**
     * AvailabilityDataFormatter constructor.
     * @param DateManager $dateManager
     * @param DayPeriodManager $dayPeriodManager
     */
    public function __construct(DateManager $dateManager, DayPeriodManager $dayPeriodManager)
    {
        $this->dateManager = $dateManager;
        $this->dayPeriodManager = $dayPeriodManager;
    }

    public function getFormattedData($data)
    {
        return array(
            'static' => $this->getStaticData($data),
            'dynamic' => $this->getDynamicData($data)
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function getStaticData(array $data)
    {
        $dates = $this->createDates($data);
        $ranges = $this->createPeriodObjects($data);

        $formattedData = array();
        foreach ($dates as $index => $date) {
            $formattedData[] = array('date' => $date, 'range' => $ranges[$index]);
        }

        return $formattedData;
    }

    protected function getDynamicData(array $data)
    {
        if (!isset($data['availability']) || !isset($data['availability']['dynamic'])) {
            return array();
        }


        $dynamic = array();
        foreach ($data['availability']['dynamic'] as $each){
            $range = $each['range'];
            $weekday = ucfirst($each['weekday']);
            $dynamic[] = array('weekday' => $weekday, 'range' => $range);
        }

        return $dynamic;
    }

    /**
     * @param $data
     * @return Date[]
     * @throws \Exception
     */
    protected function createDates($data)
    {
        if (!isset($data['availability'])) {
            return array();
        }

        $dayStrings = $this->getDayStrings($data);
        //TODO: Check if necessary now that dates and periods are pre-charged
        $dates = $this->saveDates($dayStrings);

        return $dates;
    }

    protected function saveDates($dayStrings)
    {
        $dates = array();
        foreach ($dayStrings as $day) {
            $date = $this->dateManager->merge($day);

            if ($date == null) {
                continue;
            }

            $this->createDayPeriods($date);

            $dates[] = $date;
        }

        return $dates;
    }

    protected function createDayPeriods(Date $date)
    {
        $dayId = $date->getDayId();
        $periods = $this->dayPeriodManager->createByDay($dayId);
        foreach ($periods as $period)
        {
            $this->dayPeriodManager->relateToDay($period->getId(), $dayId);
        }
    }

    protected function getDayStrings(array $data)
    {
        $daysIntervals = array_map(
            function ($object) {
                return $object['days'];
            },
            $data['availability']['static']
        );

        $dayStrings = array();
        foreach ($daysIntervals as $days){
            $dateStart = new \DateTime($days['start']);
            $dateEnd = new \DateTime($days['end']);
            $period = new \DatePeriod($dateStart, new \DateInterval('P1D'), $dateEnd);

            /** @var \DateTime $day */
            foreach ($period as $day){
                $dayStrings[] = $day->format('Y-m-d');
            }
        }

        return $dayStrings;
    }

    protected function createPeriodObjects($data)
    {
        if (!isset($data['availability'])) {
            return array();
        }

        $ranges = array_map(
            function ($object) {
                return $object['range'];
            },
            $data['availability']['static']
        );

        $periods = $this->dayPeriodManager->buildFromData($ranges);

        return $periods;
    }
}