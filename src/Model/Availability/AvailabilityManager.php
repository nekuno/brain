<?php

namespace Model\Availability;

use Model\Neo4j\GraphManager;
use Model\Proposal\Proposal;

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

    public function getByProposal(Proposal $proposal)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(proposal)')
            ->where('id(proposal) = {proposalId}')
            ->setParameter('proposalId', $proposal->getId());

        $qb->match('(proposal)-[:HAS_AVAILABILITY]->(availability:Availability)')
            ->with('availability');

        $qb->match('(availability)-[:INCLUDES]-(day:Day)')
            ->returns('{id: id(availability)} AS availability', 'collect(id(day)) AS daysIds');

        $resultSet = $qb->getQuery()->getResultSet();

        $availabilityData = $qb->getData($resultSet->current());

        return $this->build($availabilityData);
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

        if (isset($availabilityData['daysIds'])){
            $availability->setDaysIds($availabilityData['daysIds']);
        }

        return $availability;
    }
}