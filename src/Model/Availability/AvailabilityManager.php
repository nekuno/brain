<?php

namespace Model\Availability;

use Model\Date\Date;
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

        if ($resultSet->count() == 0){
            return null;
        }
        $availabilityData = $qb->getData($resultSet->current());

        return $this->build($availabilityData);
    }

    public function create()
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->create('(availability:Availability)')
            ->with('availability');

        $qb->returns('{id: id(availability)} AS availability');

        $resultSet = $qb->getQuery()->getResultSet();
        $data = $qb->getData($resultSet->current());

        return $this->build($data['availability']);
    }

    public function addStatic(Availability $availability, array $dates)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(availability:Availability)')
            ->where('id(availability) = {availabilityId}')
            ->with('availability')
            ->setParameter('availabilityId', $availability->getId());

        foreach ($dates as $index => $date)
        {
            /** @var Date $dateObject */
            $dateObject = $date['date'];

            $qb->optionalMatch('(day:Day)')
                ->where("id(day) = {dayId$index}")
                ->setParameter("dayId$index", $dateObject->getDayId());

            $qb->merge('(availability)-[includes:INCLUDES]->(day)');

            $qb->set("includes.min = {min$index}")
                ->set("includes.max = {max$index}")
                ->setParameter("min$index", $date['range']['min'])
                ->setParameter("max$index", $date['range']['max']);

            $qb->with('availability');
        }

        $qb->returns('{id: id(availability)} AS availability');

        $resultSet = $qb->getQuery()->getResultSet();
        $data = $qb->getData($resultSet->current());

        return $this->build($data['availability']);
    }

    public function addDynamic(Availability $availability, array $dynamic)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(availability:Availability)')
            ->where('id(availability) = {availabilityId}')
            ->with('availability')
            ->setParameter('availabilityId', $availability->getId());

        foreach ($dynamic as $each)
        {
            $weekday = $each['weekday'];
            $range = $each['range'];
            $qb->set("availability.$weekday = { $weekday }")
                ->setParameter($weekday, $range);
            $qb->with('availability');
        }

        $qb->returns('{id: id(availability)} AS availability');

        $resultSet = $qb->getQuery()->getResultSet();
        $data = $qb->getData($resultSet->current());

        return $this->build($data['availability']);
    }

    //TODO: Not used
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

    protected function build(array $availabilityData)
    {
        $availability = new Availability();
        $availability->setId($availabilityData['id']);

        if (isset($availabilityData['daysIds'])){
            $availability->setDaysIds($availabilityData['daysIds']);
        }

        return $availability;
    }

    public function relateToProposal(Availability $availability, Proposal $proposal)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $availabilityId = $availability->getId();
        $qb->match('(availability:Availability)')
            ->where('id(availability) = {availabilityId}')
            ->with('availability')
            ->setParameter('availabilityId', $availabilityId);

        $proposalId = $proposal->getId();
        $qb->match('(proposal:Proposal)')
            ->where('id(proposal) = {proposalId}')
            ->with('proposal')
            ->setParameter('proposalId', $proposalId);

        $qb->merge('(proposal)-[:HAS_AVAILABILITY]-(availability)');

        $result = $qb->getQuery()->getResultSet();

        return !!($result->count());
    }
}