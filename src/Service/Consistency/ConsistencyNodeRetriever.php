<?php

namespace Service\Consistency;

use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;

class ConsistencyNodeRetriever
{
    protected $graphManager;

    /**
     * ConsistencyNodeRetriever constructor.
     * @param $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    /**
     * @param $paginationSize
     * @param $offset
     * @param $label
     * @return ConsistencyNodeData[]
     */
    public function getNodeData($paginationSize, $offset, $label)
    {
        $qb = $this->graphManager->createQueryBuilder();

        if (null !== $label) {
            $qb->match("(node:$label)");
        } else {
            $qb->match('(node)');
        }

        $qb->with('node');
        $qb->skip('{offset}')
            ->limit($paginationSize)
            ->setParameter('offset', $offset);

        $qb->optionalMatch('(node)-[outgoing]->(otherNode)');
        $qb->with('node', 'outgoing', 'otherNode', 'keys(outgoing) AS relKeys');
        $qb->with('node', 'outgoing', 'otherNode', 'extract(relKey in relKeys | {key: relKey, value: outgoing[relKey]}) AS relProperties');
        $qb->with('node', '{id:id(outgoing), endNodeLabels: labels(otherNode), endNodeId: id(otherNode), startNodeLabels: labels(node), startNodeId: id(node), type: type(outgoing), properties: relProperties} AS outgoing');
        $qb->with('node', 'collect(outgoing) AS outgoings');

        $qb->optionalMatch('(node)<-[incoming]-(otherNode)');
        $qb->with('node', 'outgoings', 'incoming', 'otherNode', 'keys(incoming) AS relKeys');
        $qb->with('node', 'outgoings', 'incoming', 'otherNode', 'extract(relKey in relKeys | {key: relKey, value: incoming[relKey]}) AS relProperties');
        $qb->with('node', 'outgoings', '{id:id(incoming), startNodeLabels: labels(otherNode), startNodeId: id(otherNode), endNodeLabels: labels(node), endNodeId: id(node), type: type(incoming), properties: relProperties} AS incoming');
        $qb->with('node', 'outgoings', 'collect(incoming) AS incomings');

        $qb->returns('id(node) AS nodeId', 'labels(node) AS nodeLabels', 'extract(nodeKey in keys(node) | {key: nodeKey, value: node[nodeKey]}) AS nodeProperties', 'outgoings', 'incomings');
        $result = $qb->getQuery()->getResultSet();

        $data = array();
        foreach ($result as $row) {
            /** @var Row $row */
            $nodeData = $this->buildData($row);
            $data[] = $nodeData;
        }
        return $data;
    }

    protected function buildData(Row $row)
    {
        $nodeData = new ConsistencyNodeData();

        $nodeData->setId($row->offsetGet('nodeId'));

        $queryLabels = $row->offsetGet('nodeLabels');
        $labels = $this->extractLabels($queryLabels);
        $nodeData->setLabels($labels);

        $queryProperties = $row->offsetGet('nodeProperties');
        $properties = $this->extractProperties($queryProperties);
        $nodeData->setProperties($properties);

        $incoming = $this->buildRelationships($row, 'incomings');
        $nodeData->setIncoming($incoming);

        $outgoing = $this->buildRelationships($row, 'outgoings');
        $nodeData->setOutgoing($outgoing);

        return $nodeData;
    }

    protected function extractProperties($queryProperties)
    {
        $properties = array();
        if (null == $queryProperties)
        {
            return array();
        }
        foreach ($queryProperties as $queryProperty) {
            $properties[$queryProperty['key']] = $queryProperty['value'];
        }

        return $properties;
    }

    protected function extractLabels($queryLabels)
    {
        $labels = array();
        if (null == $queryLabels)
        {
            return array();
        }
        foreach ($queryLabels as $queryLabel) {
            $labels[] = $queryLabel;
        }

        return $labels;
    }

    protected function buildRelationships(Row $row, $queryKey)
    {
        $queryRelationships = $row->offsetGet($queryKey);

        $relationships = array();
        foreach ($queryRelationships as $queryRelationship) {
            $relationship = new ConsistencyRelationshipData();

            $relationshipId = $queryRelationship['id'];
            $relationship->setId($relationshipId);

            $type = $queryRelationship['type'];
            $relationship->setType($type);

            $propertiesQuery = $queryRelationship['properties'];
            $properties = $this->extractProperties($propertiesQuery);
            $relationship->setProperties($properties);

            $startNodeId = $queryRelationship['startNodeId'];
            $relationship->setStartNodeId($startNodeId);

            $queryStartNodeLabels = $queryRelationship['startNodeLabels'];
            $startNodeLabels = $this->extractLabels($queryStartNodeLabels);
            $relationship->setStartNodeLabels($startNodeLabels);

            $endNodeId = $queryRelationship['endNodeId'];
            $relationship->setEndNodeId($endNodeId);

            $queryEndNodeLabels = $queryRelationship['endNodeLabels'];
            $endNodeLabels = $this->extractLabels($queryEndNodeLabels);
            $relationship->setEndNodeLabels($endNodeLabels);

            $relationships[] = $relationship;
        }

        return $relationships;
    }

}