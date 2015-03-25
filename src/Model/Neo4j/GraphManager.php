<?php

namespace Model\Neo4j;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Label;
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

    /**
     * @param $id1 'node to be deleted'
     * @param $id2 'node to receive relationships'
     * @return array
     */
    public function fuseNodes($id1, $id2){

        $rels=array();

        //outgoing relationships

        //get relationships
        $qb=$this->createQueryBuilder();
        $qb->match(('(n1)-[r]->(a)'))
            ->where('id(n1)={id1}')
            ->returns('r AS rel,type(r) AS type, id(a) AS destination');
        $qb->setParameter('id1',$id1);
        $outrs=$qb->getQuery()->getResultSet();

        //create new relationships
        foreach($outrs as $row){
            $qb=$this->createQueryBuilder();
            $qb->match('(n2),(a)')
                ->where('id(n2)=id2 and id(a)=ida')
                ->merge('(n2)-[r:'.$row['type'].']->(a)');
                foreach($row['rel'] as $property=>$value){
                    $qb->add(' ON CREATE ', ' SET r.'.$property.' = '.$value.' ');
                }
            $qb->returns('r');

            $qb->setParameters(array(
                'id2'=>$id2,
                'ida'=>$row['destination']
            ));

            $rels['outgoing']=$qb->getQuery()->getResultSet();
        }

        //incoming relationships

        //get relationships
        $qb=$this->createQueryBuilder();
        $qb->match(('(n1)<-[r]-(a)'))
            ->where('id(n1)={id1}')
            ->returns('r AS rel,type(r) AS type, id(a) AS origin');
        $qb->setParameter('id1',$id1);
        $inrs=$qb->getQuery()->getResultSet();

        //create new relationships
        foreach($inrs as $row){
            $qb=$this->createQueryBuilder();
            $qb->match('(n2),(a)')
                ->where('id(n2)=id2 and id(a)=ida')
                ->merge('(n2)<-[r:'.$row['type'].']-(a)');
            foreach($row['rel'] as $property=>$value){
                $qb->add(' ON CREATE ', ' SET r.'.$property.' = '.$value.' ');
            }
            $qb->returns('r');

            $qb->setParameters(array(
                'id2'=>$id2,
                'ida'=>$row['origin']
            ));

            $rels['incoming']=$qb->getQuery()->getResultSet();
        }

        //delete n1
        $qb=$this->createQueryBuilder();
        $qb->match(('(n1)-[r]->()'))
            ->where('id(n1)={id1}')
            ->delete('r,n1');
        $qb->setParameter('id1',$id1);
        $deleted=$qb->getQuery()->getResultSet();

        return array('relationships'=>$rels, 'deleted'=>$deleted);
    }

}