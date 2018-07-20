<?php

namespace Model\Availability;

use Everyman\Neo4j\Query\ResultSet;
use Model\Neo4j\GraphManager;

class AvailabilityManager
{
    protected $graphManager;

    /**
     * AvailabilityManager constructor.
     * @param $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    public function create($data)
    {
        $qb = $this->graphManager->createQueryBuilder();
        
        $dates = $data['dates'];

        $qb->create('(availability:Availability)')
            ->with('availability');

        $qb->match('(day:Day)')
            ->where('id(day) IN {days}')
            ->with('availability', 'day')
            ->setParameter('days', $dates);
        
        $qb->merge('(availability)-[:INCLUDES]->(day)');
        
        $qb->returns('{id: id(availability)} AS availability');

        $resultSet = $qb->getQuery()->getResultSet();

        $availability = $this->build($resultSet);
    }

    protected function build(ResultSet $resultSet)
    {
        $availabilityResult = $resultSet->current()->offsetGet('availability');

        $availability = new Availability();
        $availability->setId($availabilityResult->offsetGet());
    }
}