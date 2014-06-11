<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/6/14
 * Time: 11:33 PM
 */

namespace model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class UserModel {

    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @param \Everyman\Neo4j\Client $client
     */
    public function __construct(Client $client){
        $this->client = $client;
    }

    /**
     * Creates an new User and returns the query result
     *
     * @param array $user
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function create(array $user = array()){

        $query = new Query(
            $this->client,
            "CREATE (u:User {
                _status: 'active',
                _id: '" . $user['id'] . "'
            })
            RETURN u;"
        );

        $result = $query->getResultSet();

        $response = array();

        foreach ($result as $row) {
            $response['id'] = $row['u']->getProperty('_id');
            $response['status'] = $row['u']->getProperty('_status');
        }

        return $response;
    }

    public function update(array $user = array()){
        // TODO: do your magic here
    }

    public function remove($criteria = null){
        if(is_array($criteria)){
            // TODO: remove by criteria
        } else {
            // TODO: remove by id
        }

        return false;
    }

    public function read($id = null){
        // TODO: do your magic here
    }

}
