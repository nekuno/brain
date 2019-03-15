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
        if (!isset($data['availability']) || !isset($data['availability']['static'])) {
            return array();
        }

        $staticData = $data['availability']['static'];

        if (empty($staticData)){
            return array();
        }

        $formattedData = array();
        foreach ($staticData as $datum) {
            $periodObjects = $this->createPeriodObjects($datum);

            foreach ($periodObjects as $periodObject)
            {
                $formattedData[] = $periodObject;
            }
        }

        return $formattedData;
    }

    protected function getDynamicData(array $data)
    {
        if (!isset($data['availability']) || !isset($data['availability']['dynamic'])) {
            return array();
        }

        $dynamic = array();
        foreach ($data['availability']['dynamic'] as $each) {
            $range = $each['range'];
            $weekday = ucfirst($each['weekday']);
            $dynamic[] = array('weekday' => $weekday, 'range' => $range);
        }

        return $dynamic;
    }

    /**
     * @param $datum
     * @return Date[]
     * @throws \Exception
     */
    protected function createDates($datum)
    {
        $dayStrings = $this->getDayStrings($datum);
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

            $this->saveDayPeriods($date);

            $dates[] = $date;
        }

        return $dates;
    }

    protected function saveDayPeriods(Date $date)
    {
        $dayId = $date->getDayId();
        $periods = $this->dayPeriodManager->createByDay($dayId);
        foreach ($periods as $period) {
            $this->dayPeriodManager->relateToDay($period->getId(), $dayId);
        }
    }

    protected function getDayStrings(array $data)
    {
        $daysInterval = $data['days'];

        $dateStart = new \DateTime($daysInterval['start']);
        $dateEnd = new \DateTime($daysInterval['end']);
        $period = new \DatePeriod($dateStart, new \DateInterval('P1D'), $dateEnd);

        $dayStrings = array();
        /** @var \DateTime $day */
        foreach ($period as $day) {
            $dayStrings[] = $day->format('Y-m-d');
        }
        $day->modify('+1 day');
        $dayStrings[] = $day->format('Y-m-d');

        return $dayStrings;
    }

    protected function createPeriodObjects($datum)
    {
        $dates = $this->createDates($datum);
        $ranges = $datum['range'];
        $periods = $this->dayPeriodManager->buildFromData($ranges, $dates);

        return $periods;
    }
}