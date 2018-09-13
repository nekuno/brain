<?php

namespace Model\Date;

use Model\Availability\Availability;
use Model\Neo4j\GraphManager;

class DayPeriodManager
{
    protected $graphManager;

    /**
     * DayPeriodManager constructor.
     * @param $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    /**
     * @param $dayId
     * @return DayPeriod[]
     * @throws \Exception
     */
    public function getByDay($dayId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(day:Day)')
            ->where('id(day) = {dayId}')
            ->setParameter('dayId', $dayId)
            ->with('day');

        $qb->match('(day)<-[:PERIOD_OF]-(period:DayPeriod)')
            ->with('{id: id(period), labels: labels(period)} AS period')
            ->returns('collect(period) AS periods');

        $resultSet = $qb->getQuery()->getResultSet();

        $data = $qb->getData($resultSet->current());

        return $this->buildFromResult($data);
    }

    /**
     * @param Availability $availability
     * @return DayPeriod[]
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

        $qb->match('(availability)-[:INCLUDES]-(period:DayPeriod)')
            ->with('{id: id(period), labels: labels(period)} AS period')
            ->returns('collect(period) AS periods');
        $result = $qb->getQuery()->getResultSet();

        $data = $qb->getData($result->current());

        return $this->buildFromResult($data);
    }

    /**
     * @param $dayId
     * @return DayPeriod[]
     * @throws \Exception
     */
    public function createByDay($dayId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(day:Day)')
            ->where('id(day) = {dayId}')
            ->setParameter('dayId', $dayId)
            ->with('day');

        $qb->merge('(day)<-[:PERIOD_OF]-(morning:DayPeriod:Morning)')
            ->merge('(day)<-[:PERIOD_OF]-(evening:DayPeriod:Evening)')
            ->merge('(day)<-[:PERIOD_OF]-(night:DayPeriod:Night)');

        $qb->match('(day)<-[:PERIOD_OF]-(period:DayPeriod)')
            ->with('{id: id(period), labels: labels(period)} AS period')
            ->returns('collect(period) AS periods');

        $resultSet = $qb->getQuery()->getResultSet();

        $data = $qb->getData($resultSet->current());

        return $this->buildFromResult($data);
    }

    /**
     * @param $periodId
     * @param $dayId
     * @return bool
     * @throws \Exception
     */
    public function relateToDay($periodId, $dayId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(day:Day)')
            ->where('id(day) = {dayId}')
            ->setParameter('dayId', $dayId)
            ->with('day');

        $qb->match('(period:DayPeriod)')
            ->where('id(period) = {periodId}')
            ->setParameter('periodId', $periodId)
            ->with('day', 'period');

        $qb->merge('(period)-[:PERIOD_OF]->(day)');

        $resultSet = $qb->getQuery()->getResultSet();

        return !!$resultSet->count();
    }

    /**
     * @param array $data
     * @return DayPeriod[]
     */
    protected function buildFromResult(array $data)
    {
        $periodsArray = $data['periods'];

        $periods = [];
        foreach ($periodsArray as $periodData) {
            $period = new DayPeriod();
            $period->setId($periodData['id']);

            $periodName = $this->extractPeriodName($periodData);
            $period->setName($periodName);

            $periods[] = $period;
        }

        return $periods;
    }

    /**
     * @param array $data
     * @return DayPeriod[]
     */
    public function buildFromData(array $data)
    {
        $periods = [];
        foreach ($data as $periodData) {
            $period = new DayPeriod();

            $periodName = $periodData['name'];
            $period->setName($periodName);

            $periods[] = $period;
        }

        return $periods;
    }

    protected function extractPeriodName(array $periodData)
    {
        $labels = $periodData['labels'];

        foreach ($labels as $label) {
            if ($label !== 'DayPeriod') {
                return $label;
            }
        }

        return null;
    }
}