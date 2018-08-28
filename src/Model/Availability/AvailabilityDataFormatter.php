<?php

namespace Model\Availability;

use Model\Date\Date;
use Model\Date\DateManager;

class AvailabilityDataFormatter
{
    protected $dateManager;

    /**
     * AvailabilityDataFormatter constructor.
     * @param DateManager $dateManager
     */
    public function __construct(DateManager $dateManager)
    {
        $this->dateManager = $dateManager;
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
        $dates = $this->createDateObjects($data);
        $ranges = $this->createTimeRanges($data);
        $ids = array();
        foreach ($dates as $index => $date) {
            $ids[] = array('date' => $date, 'range' => $ranges[$index]);
        }

        return $ids;
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
    protected function createDateObjects($data)
    {
        if (!isset($data['availability'])) {
            return array();
        }

        $days = array_map(
            function ($object) {
                return $object['day'];
            },
            $data['availability']['static']
        );
        $dates = array();
        foreach ($days as $index => $day) {
            $date = $this->dateManager->merge($day);
            if ($date !== null) {
                $dates[$index] = $date;
            }
        }

        return $dates;
    }

    protected function createTimeRanges($data)
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

        $secondsInDay = 24 * 3600;
        foreach ($ranges as &$range) {
            $range['min'] = isset($range['min']) ? $range['min'] : 0;
            $range['max'] = isset($range['max']) ? $range['min'] : $secondsInDay;

        }

        return $ranges;
    }
}