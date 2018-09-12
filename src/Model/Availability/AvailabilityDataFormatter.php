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

        $ranges = array();
        foreach ($data['availability']['dynamic'] as $datum) {
            $weekday = $datum['weekday'];
            $range = $datum['range'];

            $ranges[] = array('weekday' => $weekday, 'range' => array($range['min'], $range['max']));
        }

        return $ranges;
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
        $days = array_map(
            function ($object) {
                return $object['day'];
            },
            $data['availability']['static']
        );

        return $days;
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