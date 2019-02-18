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

        $qb->match('(availability)-[:INCLUDES{static:true}]->(period:DayPeriod)')
            ->returns('{id: id(availability), properties: properties(availability)} AS availability', 'collect(id(period)) AS periodIds');

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

        $qb->match('(availability)-[:INCLUDES{static:true}]->(period:DayPeriod)')
            ->returns('{id: id(availability), properties: properties(availability)} AS availability', 'collect(id(period)) AS periodIds');

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

        $qb->returns('{id: id(availability), properties: properties(availability)} AS availability');

        $resultSet = $qb->getQuery()->getResultSet();
        $data = $qb->getData($resultSet->current());

        return $this->build($data);
    }

    /**
     * @param Availability $availability
     * @param DayPeriod[] $staticData
     * @return Availability
     * @throws \Exception
     */
    public function addStatic(Availability $availability, array $staticData)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(availability:Availability)')
            ->where('id(availability) = {availabilityId}')
            ->with('availability')
            ->setParameter('availabilityId', $availability->getId());

        foreach ($staticData as $index => $period) {

            $qb->optionalMatch("(period$index:DayPeriod)")
                ->where("id(period$index) = {periodId$index}")
                ->setParameter("periodId$index", $period->getId());

            $qb->merge("(availability)-[includes:INCLUDES{static:true}]->(period$index)");
            $qb->with('availability');
        }

        $qb->returns('{id: id(availability), properties: properties(availability)} AS availability');

        $resultSet = $qb->getQuery()->getResultSet();
        $data = $qb->getData($resultSet->current());

        return $this->build($data);
    }

    public function addDynamic(Availability $availability, array $dynamic)
    {
        if (empty($dynamic)) {
            return null;
        }

        foreach ($dynamic as $each) {

            $weekday = $each['weekday'];
            $range = $each['range'];

            foreach ($range as $index => $dayPeriod) {

                $qb = $this->graphManager->createQueryBuilder();

                $qb->match('(availability:Availability)')
                    ->where('id(availability) = {availabilityId}')
                    ->with('availability')
                    ->setParameter('availabilityId', $availability->getId());

                $qb->set("availability.$weekday = COALESCE(availability.$weekday, [])");
                $qb->with('availability');
                $qb->set("availability.$weekday = availability.$weekday + ['$dayPeriod']");
                $qb->with('availability');

                $qb->match("(day:$weekday)");
                $qb->match("(day)-[:PERIOD_OF]-(period:$dayPeriod)")
                    ->merge('(availability)-[:INCLUDES]->(period)');
                $qb->with('availability');

                $qb->returns('{id: id(availability), properties: properties(availability)} AS availability');

                $resultSet = $qb->getQuery()->getResultSet();
            }
        }

        $qb = $this->graphManager->createQueryBuilder();
        $data = $qb->getData($resultSet->current());

        return $this->build($data);
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

        $qb->returns('{id: id(availability), properties: properties(availability)} AS availability');

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

    protected function build(array $data)
    {
        $availabilityData = $data['availability'];
        $availability = new Availability();
        $availability->setId($availabilityData['id']);

        $this->setDynamicData($availability, $availabilityData);

        if (isset($data['periodIds'])) {
            $availability->setPeriodIds($data['periodIds']);
        }

        return $availability;
    }

    protected function setDynamicData(Availability $availability, array $availabilityData)
    {
        if (!isset($availabilityData['properties'])) {
            return;
        }

        $properties = $availabilityData['properties'];
        $dynamicData = array();
        foreach ($properties as $weekday => $range) {
            $dynamicData[] = ['weekday' => $weekday, 'range' => $range];
        }
        $availability->setDynamic($dynamicData);
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
            ->with('proposal', 'availability')
            ->setParameter('proposalId', (integer)$proposalId);

        $qb->merge('(proposal)-[:HAS_AVAILABILITY]-(availability)');

        $result = $qb->getQuery()->getResultSet();

        return !!($result->count());
    }
}