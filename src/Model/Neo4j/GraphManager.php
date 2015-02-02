<?php

namespace Model\Neo4j;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Label;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class GraphManager
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
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