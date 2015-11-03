<?php

namespace Model\Neo4j;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class GraphManager implements LoggerAwareInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Create a QueryBuilder instance
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * @param string $cypher
     * @param array $parameters
     * @return Query
     */
    public function createQuery($cypher = '', $parameters = array())
    {
        $query = new Query($this->client, $cypher, $parameters);

        if ($this->logger instanceof LoggerInterface && $query instanceof LoggerAwareInterface) {
            $query->setLogger($this->logger);
        }

        return $query;
    }

    /**
     * @param $name
     * @return Label
     */
    public function makeLabel($name)
    {
        return $this->client->makeLabel($name);
    }

    /**
     * @param $labels
     * @return array
     */
    public function makeLabels($labels)
    {

        $return = array();
        foreach ($labels as $label) {
            $return[] = $this->makeLabel($label);
        }

        return $return;

    }

    /** Copies every relationship and property from node 1 to node 2 and deletes node 1
     *  Returns an array with every relationship for logging and debugging
     * @param $id1 'node to be deleted'
     * @param $id2 'node to receive relationships'
     * @return array
     */
    public function fuseNodes($id1, $id2)
    {

        $rels = array();

        $rels = array_merge($rels, $this->copyRelationships($id1, $id2, 'outgoing'));

        $rels = array_merge($rels, $this->copyRelationships($id1, $id2, 'incoming'));

        $props = $this->copyProperties($id1, $id2);

        //delete n1
        $qb = $this->createQueryBuilder();
        $qb->match(('(n1)'))
            ->where('id(n1)={id1}')
            ->optionalMatch('(n1)-[r1]->()')
            ->optionalMatch(('(n1)<-[r2]-()'))
            ->delete('r1,r2,n1')
            ->returns('count(r1)+count(r2) as amount');
        $qb->setParameter('id1', $id1);
        $deleted = $qb->getQuery()->getResultSet();

        return array('relationships' => $rels,
            'properties' => $props,
            'deleted' => $deleted);
    }

    protected function copyRelationships($id1, $id2, $mode = 'outgoing')
    {

        //get relationships
        $qb = $this->createQueryBuilder();
        if ($mode == 'outgoing') {
            $qb->match('(n1)-[r]->(a)');
        } else {
            $qb->match('(n1)<-[r]-(a)');
        }

        $qb->where('id(n1)={id1}', 'id(a) <> {id1}')
            ->returns('r AS rel,type(r) AS type, id(a) AS destination');
        $qb->setParameter('id1', $id1);
        $rs = $qb->getQuery()->getResultSet();

        //create new relationships
        $rels = array();
        foreach ($rs as $row) {
            $qb = $this->createQueryBuilder();
            $qb->match('(n2),(a)')
                ->where('id(n2)={id2} and id(a)={ida}');
            if ($mode == 'outgoing') {
                $qb->merge('(n2)-[r:' . $row['type'] . ']->(a)');
            } else {
                $qb->merge('(n2)<-[r:' . $row['type'] . ']-(a)');
            }
            $qb->merge('(n2)-[r:' . $row['type'] . ']->(a)');

            foreach ($row['rel']->getProperties() as $property => $value) {
                if (is_string($value)) {
                    $qb->add(' ON CREATE ', ' SET r.' . $property . ' = "' . $value . '" ');
                } else {
                    $qb->add(' ON CREATE ', ' SET r.' . $property . ' = ' . $value . ' ');
                }

            }
            $qb->returns('r, id(r) AS id');

            $qb->setParameters(array(
                'id2' => $id2,
                'ida' => $row['destination']
            ));

            $rels[] = $qb->getQuery()->getResultSet();
        }

        return $rels;

    }

    private function copyProperties($id1, $id2)
    {
        //get properties
        $qb = $this->createQueryBuilder();

        $qb->match('(n1)')
            ->where('id(n1)={id1}')
            ->returns('n1');
        $qb->setParameter('id1', $id1);
        $rs = $qb->getQuery()->getResultSet();

        /** @var Node $node */
        $node = $rs->current()->offsetGet('n1');
        $properties = $node->getProperties();

        $qb = $this->createQueryBuilder();
        $qb->match('(n2)')
            ->where('id(n2)={id2}');
        $qb->setParameter('id2', $id2);
        $sets = array();
        foreach ($properties as $key => $property) {
            $sets[] = "n2.$key = $property";
        }
        $qb->set($sets);

        $qb->returns('n1');

        $rs = $qb->getQuery()->getResultSet();

        $node = $rs->current()->offsetGet('n2');

        return $node->getProperties();
    }

}