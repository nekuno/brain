<?php

namespace Model\Neo4j;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

/**
 * Class Constraints
 *
 * @package Model\Neo4j
 */
class Constraints
{

    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @param \Everyman\Neo4j\Client $client
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    /**
     * Load the constraints
     *
     * This is mean to be executed with a clear db, ony once.
     *
     * @throws \Exception
     */
    public function load()
    {
        $constraints = array();
        $constraints[] = "CREATE CONSTRAINT ON (u:User) ASSERT u.qnoow_id IS UNIQUE;";

        foreach ($constraints as $query) {
            $neo4jQuery = new Query(
                $this->client,
                $query
            );

            try {
                $result = $neo4jQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;

                return;
            }
        }
    }
}