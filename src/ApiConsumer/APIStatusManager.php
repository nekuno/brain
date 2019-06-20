<?php

namespace ApiConsumer;

use Model\Neo4j\GraphManager;

class APIStatusManager
{
    protected $graphManager;

    /**
     * APIStatusManager constructor.
     * @param GraphManager $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    /**
     * @param $resourceOwner
     * @return APIStatus
     */
    public function checkAPIStatus($resourceOwner)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $label = $this->buildLabel($resourceOwner);

        $qb->merge("(resourceOwner: $label)")
            ->with('resourceOwner');

        $qb->returns('{percentageUsed: resourceOwner.percentageUsed, timeChecked: resourceOwner.timeChecked} AS data');

        $result = $qb->getQuery()->getResultSet();
        $data = $qb->getData($result->current());

        return $this->buildOne($data['data']);
    }

    /**
     * @param $resourceOwner
     * @param APIStatus $status
     * @return APIStatus
     * @throws \Exception
     */
    public function saveAPIStatus($resourceOwner, APIStatus $status)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $label = $this->buildLabel($resourceOwner);

        $qb->merge("(resourceOwner: $label)")
            ->with('resourceOwner');

        $qb->set('resourceOwner.percentageUsed = {percentageUsed}')
            ->setParameter('percentageUsed', $status->getPercentageUsed())
            ->with('resourceOwner');

        $qb->set('resourceOwner.timeChecked = {timeChecked}')
            ->setParameter('timeChecked', $status->getTimeChecked())
            ->with('resourceOwner');

        $qb->returns('{percentageUsed: resourceOwner.percentageUsed, timeChecked: resourceOwner.timeChecked} AS data');

        $result = $qb->getQuery()->getResultSet();
        $data = $qb->getData($result->current());

        return $this->buildOne($data['data']);
    }

    protected function buildLabel($resourceOwner)
    {
        $resourceOwner = ucfirst($resourceOwner);
        $label = 'API'.$resourceOwner;

        return $label;
    }

    protected function buildOne(array $data)
    {
        $status = new APIStatus();

        if (isset($data['percentageUsed'])){
            $status->setPercentageUsed($data['percentageUsed']);
        }

        if (isset($data['timeChecked'])){
            $status->setTimeChecked($data['timeChecked']);
        }

        return $status;
    }

}