<?php

namespace Model\Date;

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

        return $this->build($data);
    }

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

        return $this->build($data);
    }

    /**
     * @param array $data
     * @return DayPeriod[]
     */
    protected function build(array $data)
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

    protected function extractPeriodName(array $periodData)
    {
        $labels = $periodData['labels'];

        foreach ($labels as $label) {
            if ($label !== 'DayPeriod') {
                return strtolower($label);
            }
        }

        return null;
    }
}