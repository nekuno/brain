<?php

namespace Model\Availability;

use Model\Date\DayPeriod;
use Model\Neo4j\GraphManager;
use Model\Proposal\Proposal;
use Model\User\User;

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

    public function getByUser(User $user)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(user:UserEnabled)')
            ->where('user.qnoow_id = {userId}')
            ->setParameter('userId', $user->getId());

        $qb->match('(user)-[:HAS_AVAILABILITY]->(availability:Availability)')
            ->with('availability');

        $qb->match('(availability)-[:INCLUDES]->(period:DayPeriod)')
            ->returns('{id: id(availability)} AS availability', 'collect(id(period)) AS periodIds');

        $resultSet = $qb->getQuery()->getResultSet();

        if ($resultSet->count() == 0) {
            return null;
        }
        $availabilityData = $qb->getData($resultSet->current());

        return $this->build($availabilityData);
    }

    public function getByProposal(Proposal $proposal)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(proposal)')
            ->where('id(proposal) = {proposalId}')
            ->setParameter('proposalId', $proposal->getId());

        $qb->match('(proposal)-[:HAS_AVAILABILITY]->(availability:Availability)')
            ->with('availability');

        $qb->match('(availability)-[:INCLUDES]->(period:DayPeriod)')
            ->returns('{id: id(availability)} AS availability', 'collect(id(period)) AS periodIds');

        $resultSet = $qb->getQuery()->getResultSet();

        if ($resultSet->count() == 0) {
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

    public function addStatic(Availability $availability, array $staticData)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(availability:Availability)')
            ->where('id(availability) = {availabilityId}')
            ->with('availability')
            ->setParameter('availabilityId', $availability->getId());

        foreach ($staticData as $index => $staticDatum) {
            /** @var DayPeriod[] $dayPeriods */
            $dayPeriods = $staticDatum['range'];

            foreach ($dayPeriods as $secondIndex => $dayPeriod)
            {
                $qb->optionalMatch("(period$index$secondIndex:DayPeriod)")
                    ->where("id(dayPeriod) = {periodId$index$secondIndex}")
                    ->setParameter("periodId$index$secondIndex", $dayPeriod->getId());

                $qb->merge("(availability)-[includes:INCLUDES]->(period$index$secondIndex)");
                $qb->with('availability');
            }
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

        foreach ($dynamic as $each) {
            $weekday = $each['weekday'];
            $range = $each['range'];
            $qb->match("(day:$weekday)");
            foreach($range as $index => $dayPeriod)
            {
                $qb->match("(day)-[:PERIOD_OF]-(period:$dayPeriod)");
                $qb->merge('(availability)-[:INCLUDES]->(period)');
                $qb->with('availability', 'day');
            }

            $qb->with('availability');
        }

        $qb->returns('{id: id(availability)} AS availability');

        $resultSet = $qb->getQuery()->getResultSet();
        $data = $qb->getData($resultSet->current());

        return $this->build($data['availability']);
    }

    public function relateToUser(Availability $availability, User $user)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(availability:Availability)')
            ->where('id(availability) = {availabilityId}')
            ->with('availability')
            ->setParameter('availabilityId', $availability->getId());

        $qb->match('(user:UserEnabled)')
            ->where('user.qnoow_id = {userId}')
            ->with('availability', 'user')
            ->setParameter('userId', $user->getId());

        $qb->merge('(user)-[:HAS_AVAILABILITY]->(availability)');

        $resultSet = $qb->getQuery()->getResultSet();
        $created = !!($resultSet->count());

        return $created;
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

        $qb->optionalMatch('(availability)-[includes:INCLUDES]->(:DayPeriod)')
            ->delete('includes')
            ->with('availability');

        $qb->match('(dayPeriod:DayPeriod)')
            ->where('id(dayPeriod) IN {dayPeriods}')
            ->with('availability', 'dayPeriod')
            ->setParameter('dayPeriods', $dates);

        $qb->merge('(availability)-[:INCLUDES]->(dayPeriod)');

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

        if (isset($availabilityData['periodIds'])) {
            $availability->setPeriodIds($availabilityData['periodIds']);
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
            ->setParameter('availabilityId', (integer)$availabilityId);

        $proposalId = $proposal->getId();
        $qb->match('(proposal:Proposal)')
            ->where('id(proposal) = {proposalId}')
            ->with('proposal')
            ->setParameter('proposalId', (integer)$proposalId);

        $qb->merge('(proposal)-[:HAS_AVAILABILITY]-(availability)');

        $result = $qb->getQuery()->getResultSet();

        return !!($result->count());
    }
}