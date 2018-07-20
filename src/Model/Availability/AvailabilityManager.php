<?php

namespace Model\Availability;

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
        $availabilityData = $qb->getData($resultSet->current());

        return $this->build($availabilityData);
    }

    public function update($availabilityId, $data)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $dates = $data['dates'];

        $qb->match('(availability:Availability)')
            ->where('id(availability) = {availabilityId}')
            ->with('availability')
            ->setParameter('availabilityId', $availabilityId);

        $qb->optionalMatch('(availability)-[includes:INCLUDES]-(:Day)')
            ->delete('includes')
            ->with('availability');

        $qb->match('(day:Day)')
            ->where('id(day) IN {days}')
            ->with('availability', 'day')
            ->setParameter('days', $dates);

        $qb->merge('(availability)-[:INCLUDES]->(day)');

        $qb->returns('{id: id(availability)} AS availability');

        $resultSet = $qb->getQuery()->getResultSet();

        $availabilityData = $qb->getData($resultSet->current());

        return $this->build($availabilityData);
    }

    public function delete($availabilityId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(availability:Availability)')
            ->where('id(availability) = {availabilityId}')
            ->with('availability')
            ->setParameter('availabilityId', $availabilityId);

        $qb->detachDelete('availability');

        $qb->getQuery()->getResultSet();

        return true;
    }

    public function build(array $availabilityData)
    {
        $availability = new Availability();
        $availability->setId($availabilityData['id']);

        return $availability;
    }
}