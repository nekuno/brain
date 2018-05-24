<?php

namespace Service\Consistency;

use Everyman\Neo4j\Relationship;
use Model\Neo4j\GraphManager;
use Service\Consistency\ConsistencyErrors\ConsistencyError;
use Service\Consistency\ConsistencyErrors\MissingPropertyConsistencyError;
use Service\Consistency\ConsistencyErrors\ReverseRelationshipConsistencyError;

class ConsistencySolver
{
    protected  $graphManager;

    /**
     * ConsistencySolver constructor.
     * @param $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    public function solve(ConsistencyError $error){
        switch (get_class($error)){
            case MissingPropertyConsistencyError::class:
                /** @var $error MissingPropertyConsistencyError */
                return $this->writeDefaultProperty($error);
            case ReverseRelationshipConsistencyError::class:
                /** @var $error ReverseRelationshipConsistencyError */
            return $this->reverseRelationship($error);
            default:
                return false;
        }
    }

    protected function writeDefaultProperty(MissingPropertyConsistencyError $error)
    {
        $default = $error->getDefaultProperty();
        if (!$default) {
            return false;
        }

        $name = $error->getPropertyName();
        $nodeId = $error->getNodeId();

        return $this->writeNodeProperty($nodeId, $name, $default);
    }

    protected function writeNodeProperty($nodeId, $propertyName, $propertyValue)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(node)')
            ->where('id(node) = {nodeId}')
            ->setParameter('nodeId', (integer)$nodeId);

        if (strpos($propertyValue, 'node.') !== false){
            $qb->set("node.$propertyName = $propertyValue");

        } else {
            $qb->set("node.$propertyName = {value}")
                ->setParameter('value', $propertyValue);
        }

        $qb->getQuery()->getResultSet();

        return true;
    }

    protected function reverseRelationship(ReverseRelationshipConsistencyError $error)
    {
        $nodeId = $error->getNodeId();
        $relationshipId = $error->getRelationshipId();

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(node)')
            ->where('id(node) = {nodeId}')
            ->setParameter('nodeId', (integer)$nodeId);
        $qb->match('(node)-[r]-(a)')
            ->where('id(r) = {relationshipId}')
            ->setParameter('relationshipId', (integer)$relationshipId);

        $qb->returns('r');
        
        $result = $qb->getQuery()->getResultSet();
        
        /** @var Relationship $relationship */
        $relationship = $result->current()->offsetGet('r');
        
        $properties = $relationship->getProperties();
        $type = $relationship->getType();
        $startId = $relationship->getStartNode()->getId();
        $endId = $relationship->getEndNode()->getId();

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(startNode)')
            ->where('id(startNode) = {startNodeId}')
            ->setParameter('startNodeId', (integer)$startId);
        $qb->match('(endNode)')
            ->where('id(endNode) = {endNodeId}')
            ->setParameter('endNodeId', (integer)$endId);
        $qb->match('(startNode)-[r]->(endNode)')
            ->where('id(r) = {relationshipId}')
            ->setParameter('relationshipId', (integer)$relationshipId);

        $qb->create("(endNode)-[new:$type]->(startNode)");

        foreach ($properties as $name=>$value)
        {
            $qb->set("new.$name = {$name}")
                ->setParameter($name, $value);
        }

        $qb->delete('r');

        $qb->returns('new');

        $result = $qb->getQuery()->getResultSet();

        return $result->count() > 0;
    }
}