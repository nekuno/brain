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
    public function getByAvailabilityStatic(Availability $availability)
    {
        $availabilityId = $availability->getId();

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(availability)')
            ->where('id(availability) = {availabilityId}')
            ->with('availability')
            ->setParameter('availabilityId', $availabilityId);

        $qb->match('(availability)-[:INCLUDES{static: true}]-(period:DayPeriod)')
            ->with('{id: id(period), labels: labels(period)} AS period')
            ->returns('collect(period) AS periods');
        $result = $qb->getQuery()->getResultSet();

        $data = $qb->getData($result->current());

        return $this->buildFromResult($data);
    }

    protected function getId(DayPeriod $dayPeriod)
    {
        $dayId = $dayPeriod->getDate()->getDayId();
        $name = $dayPeriod->getName();

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(day:Day)')
            ->where('id(day) = {dayId}')
            ->setParameter('dayId', $dayId)
            ->with('day');

        $qb->match("(day)<-[:PERIOD_OF]-(period:$name)")
            ->returns('id(period) AS periodId');

        $result = $qb->getQuery()->getResultSet();
        if ($result->count() == 0) {
            return null;
        }

        $data = $qb->getData($result->current());

        return $data['periodId'];
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
            ->merge('(day)<-[:PERIOD_OF]-(night:DayPeriod:Night)')
            ->with('day');

        $qb->match('(day)<-[:PERIOD_OF]-(period:DayPeriod)')
            ->with('{id: id(period), labels: labels(period)} AS period')
            ->returns('collect(period) AS periods');

        $resultSet = $qb->getQuery()->getResultSet();

        $data = $qb->getData($resultSet->current());

        return $this->buildFromResult($data);
    }

    public function createAll()
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(day:Day)');

        $qb->merge('(day)<-[:PERIOD_OF]-(morning:DayPeriod:Morning)')
            ->merge('(day)<-[:PERIOD_OF]-(afternoon:DayPeriod:Afternoon)')
            ->merge('(day)<-[:PERIOD_OF]-(night:DayPeriod:Night)')
            ->with('day');

        $qb->match('(day)<-[:PERIOD_OF]-(period:DayPeriod)')
            ->returns('count(period) AS periods');

        $resultSet = $qb->getQuery()->getResultSet();
        $data = $qb->getData($resultSet->current());

        return $data['periods'];
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
     * @param array $periodStrings
     * @param Date[] $dates
     * @return DayPeriod[]
     */
    public function buildFromData(array $periodStrings, array $dates = [])
    {
        $periods = [];
        foreach ($periodStrings as $periodName) {
            foreach ($dates as $date) {
                $period = new DayPeriod();
                $period->setName($periodName);
                $period->setDate($date);

                $id = $this->getId($period);
                $period->setId($id);

                $periods[] = $period;
            }
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