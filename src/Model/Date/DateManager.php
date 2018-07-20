<?php

namespace Model\Date;

use Model\Neo4j\GraphManager;

class DateManager
{
    protected $graphManager;

    /**
     * DateManager constructor.
     * @param $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    /**
     * @param $dateString yyyy-mm-dd
     * @return Date|null
     * @throws \Exception
     */
    public function merge($dateString)
    {
        $date = new Date($dateString);

        if ($date === null) {
            return null;
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
        $qb->set("(day:$weekday");

        $qb->returns('year.value AS year', 'month.value AS month', 'day.value AS day', 'id(day) AS dayId', 'created');

        $result = $qb->getQuery()->getResultSet();
        $row = $result->current();

        $date->setYear($row->offsetGet('year'));
        $date->setMonth($row->offsetGet('month'));
        $date->setDay($row->offsetGet('day'));
        $date->setDayId($row->offsetGet('dayId'));

        if ($row->offsetGet('created')) {
            $previousDateString = $this->buildPreviousDate($dateString);
            $previousDate = $this->merge($previousDateString);
            $this->createNext($previousDate, $date);
        }

        return $date;
    }

    protected function buildPreviousDate($dateString)
    {
        $date = new \DateTime($dateString);
        $date->modify('-1 day');

        return $date->format('yyyy-mm-dd');
    }

    protected function getWeekday($dateString)
    {
        $date = new \DateTime($dateString);

        return $date->format('l');
    }

    protected function buildDate($dateString)
    {
        try {
            $date = new Date($dateString);
        } catch (\Exception $e) {
            return null;
        }

        return $date;
    }

    protected function createNext(Date $previous, Date $target)
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
        foreach ($result as $row)
        {
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
        if (empty($weekdays)){
            return false;
        }

        $possibilities = implode(' OR day:', $weekdays);

        $restriction = "(day:$possibilities)";
        return $restriction;
    }
}