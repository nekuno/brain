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

}