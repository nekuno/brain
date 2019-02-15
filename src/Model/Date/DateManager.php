<?php

namespace Model\Date;

use Model\Availability\Availability;
use Model\Neo4j\GraphManager;

class DateManager
{
    protected $graphManager;

    /**
     * DateManager constructor.
     * @param GraphManager $graphManager
     * @param $lastYear
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    /**
     * @param Availability $availability
     * @return Date[]
     * @throws \Exception
     */
    public function getByAvailability(Availability $availability)
    {
        $availabilityId = $availability->getId();

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(availability)')
            ->where('id(availability) = {availabilityId}')
            ->with('availability')
            ->setParameter('availabilityId', $availabilityId);

        $qb->match('(availability)-[:INCLUDES]-(day:Day)-[:DAY_OF]->(month:Month)-[:MONTH_OF]-(year:Year)')
            ->returns('year.value AS year', 'month.value AS month', 'day.value AS day', 'id(day) AS dayId');

        $result = $qb->getQuery()->getResultSet();

        $dates = array();
        foreach ($result as $row) {
            $data = $qb->getData($row);
            $date = $this->build($data);
            $dates[] = $date;
        }

        return $dates;
    }

    /**
     * @param DayPeriod $dayPeriod
     * @return Date
     * @throws \Exception
     */
    public function getByPeriod(DayPeriod $dayPeriod)
    {
        $periodId = $dayPeriod->getId();

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(period:DayPeriod)')
            ->where('id(period) = {periodId}')
            ->setParameter('periodId', $periodId)
            ->with('period');

        $qb->match('(day:Day)<-[:PERIOD_OF]-(period)')
            ->with('day');

        $qb->match('(day:Day)-[:DAY_OF]->(month:Month)-[:MONTH_OF]-(year:Year)')
            ->returns('year.value AS year', 'month.value AS month', 'day.value AS day', 'id(day) AS dayId');

        $result = $qb->getQuery()->getResultSet();

        $data = $qb->getData($result->current());
        $date = $this->build($data);

        return $date;
    }

    /**
     * @param $dateString yyyy-mm-dd
     * @param $limitDateString yyyy-mm-dd
     * @return Date|null
     * @throws \Exception
     */
    public function merge($dateString, $limitDateString = null)
    {
        $date = $this->buildDate($dateString);
        if ($date == null)
        {
            return null;
        }
        if ($limitDateString){
            $endDate = $this->buildDate($limitDateString);
            if ($date->jsonSerialize() == $endDate->jsonSerialize()){
                return null;
            }
        }

        $qb = $this->graphManager->createQueryBuilder();

        $qb->merge('(year:Year{value: {year}})')
            ->with('year')
            ->setParameter('year', $date->getYear());

        $qb->merge('(year)<-[:MONTH_OF]-(month:Month{value: {month}})')
            ->with('year', 'month')
            ->setParameter('month', $date->getMonth());

        $qb->merge('(month)<-[:DAY_OF]-(day:Day{value:{day}})')
            ->onCreate('SET day.new = true')
            ->onMatch('SET day.new = false')
            ->with('year', 'month', 'day', 'day.new AS created')
            ->remove('day.new')
            ->setParameter('day', $date->getDay());

        $weekday = $this->getWeekday($dateString);
        $qb->set("day:$weekday");

        $qb->returns('year.value AS year', 'month.value AS month', 'day.value AS day', 'id(day) AS dayId', 'created');

        $result = $qb->getQuery()->getResultSet();
        $row = $result->current();

        $data = $qb->getData($row);
        $date = $this->build($data);

        if ($data['created']) {
            $previousDateString = $this->buildPreviousDate($dateString);
            $previousDate = $this->merge($previousDateString, $limitDateString);
            if (null !== $previousDate)
            {
                $this->createNext($previousDate, $date);
            }
        }

        return $date;
    }

    //No limit, assumes Date from dateString is built
    public function mergeNextDate($dateString)
    {
        $nextDateString = $this->buildNextDate($dateString);
        return $this->merge($nextDateString);
    }

    protected function buildPreviousDate($dateString)
    {
        $date = new \DateTime($dateString);
        $date->modify('-1 day');

        return $date->format('Y-m-d');
    }

    protected function buildNextDate($dateString)
    {
        $date = new \DateTime($dateString);
        $date->modify('+1 day');

        return $date->format('Y-m-d');
    }

    protected function getWeekday($dateString)
    {
        $date = new \DateTime($dateString);

        return ucfirst($date->format('l'));
    }

    protected function buildDate($dateString)
    {
        try {
            $date = new Date();
            $date->setDate($dateString);
        } catch (\Exception $e) {
            return null;
        }

        return $date;
    }

    public function createNext(Date $previous, Date $target)
    {
        $previousId = $previous->getDayId();
        $targetId = $target->getDayId();

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(previous:Day)')
            ->where('id(previous) = {previousId}')
            ->with('previous')
            ->setParameter('previousId', $previousId);

        $qb->match('(target:Day)')
            ->where('id(target) = {targetId}')
            ->with('previous', 'target')
            ->setParameter('targetId', $targetId);

        $qb->merge('(previous)-[next:NEXT]->(target)');

        $qb->returns('id(next) AS nextId');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('nextId');
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param array $weekdays
     * @return Date[]
     * @throws \Exception
     */
    public function getIntervalDates($startDate, $endDate, $weekdays = [])
    {
        $startDate = $this->merge($startDate);
        $endDate = $this->merge($endDate);

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(start:Day)')
            ->where('id(start) = {startId}')
            ->with('start')
            ->setParameter('startId', $startDate->getDayId());

        $qb->match('(end:Day)')
            ->where('id(end) = {endId}')
            ->with('start', 'end')
            ->setParameter('endId', $endDate->getDayId());

        $qb->match('p = (start)-[:NEXT*]->(end)')
            ->with('nodes(path) AS days')
            ->unwind('days AS day');

        $weekdayRestriction = $this->buildWeekdayRestriction($weekdays);
        $qb->with('day')
            ->where($weekdayRestriction);

        $qb->match('(day)-[:DAY_OF]->(month:Month)-[:MONTH_OF]->(year:Year)');

        $qb->returns('year.value AS year', 'month.value AS month', 'day.value AS day', 'id(day) AS dayId');

        $result = $qb->getQuery()->getResultSet();

        $dates = array();
        foreach ($result as $row) {
            $date = new Date();
            $date->setDay($row->offsetGet('day'));
            $date->setMonth($row->offsetGet('month'));
            $date->setYear($row->offsetGet('year'));
            $date->setDayId($row->offsetGet('dayId'));

            $dates[] = $date;
        }

        return $dates;

    }

    protected function buildWeekdayRestriction(array $weekdays)
    {
        if (empty($weekdays)) {
            return false;
        }

        $possibilities = implode(' OR day:', $weekdays);

        $restriction = "(day:$possibilities)";

        return $restriction;
    }

    protected function build(array $resultData)
    {
        $date = new Date();

        $date->setYear($resultData['year']);
        $date->setMonth($resultData['month']);
        $date->setDay($resultData['day']);
        $date->setDayId($resultData['dayId']);

        return $date;
    }
}