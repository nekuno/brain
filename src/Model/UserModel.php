<?php

namespace Model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

/**
 * Class UserModel
 *
 * @package Model
 */
class UserModel
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
     * Creates an new User and returns the query result
     *
     * @param array $user
     * @throws \Exception
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function create(array $user = array())
    {
        $query = new Query(
            $this->client,
            "CREATE (u:User {
                status: 'active',
                qnoow_id: " . $user['id'] . ",
                username: '" . $user['username'] . "',
                email: '" . $user['email'] . "'
            })
            RETURN u;"
        );

        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->parseResultSet($result);
    }

    /**
     * @param $resultSet
     * @return array
     */
    private function parseResultSet($resultSet)
    {
        $users = array();

        foreach ($resultSet as $row) {
            $user    = array(
                'qnoow_id' => $row['u']->getProperty('qnoow_id'),
                'username' => $row['u']->getProperty('username'),
                'email'    => $row['u']->getProperty('email'),
            );
            $users[] = $user;
        }

        return $users;

    }

    /**
     * @param array $user
     */
    public function update(array $user = array())
    {
        // TODO: do your magic here
    }

    /**
     * @param null $id
     * @return array
     * @throws \Exception
     */
    public function remove($id = null)
    {
        $queryString = "MATCH (u:User {qnoow_id:" . $id . "}) DELETE u;";
        $query       = new Query($this->client, $queryString);

        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->parseResultSet($result);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAll()
    {
        $queryString = "MATCH (u:User) RETURN u;";
        $query       = new Query($this->client, $queryString);

        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->parseResultSet($result);

    }

    /**
     * @param null $id
     * @return array
     * @throws \Exception
     */
    public function getById($id = null)
    {
        $queryString = "MATCH (u:User { qnoow_id : " . $id . "}) RETURN u;";
        $query       = new Query($this->client, $queryString);

        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->parseResultSet($result);

    }
}
