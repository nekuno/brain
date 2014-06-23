<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/6/14
 * Time: 11:33 PM
 */

namespace Model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Exception\QueryErrorException;

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
                status: 1,
                qnoow_id: " . $user['id'] . ",
                username: '" . $user['username'] . "',
                email: '"    . $user['email'] . "'
            })
            RETURN u;"
        );

        try{
            $result = $query->getResultSet();
        }catch (\Exception $e){
            throw $e;
        }

        return $this->parseResultSet($result);
    }

    public function update(array $user = array()){
        // TODO: do your magic here
    }

    public function remove($id = null){
        $queryString = "MATCH (u:User {qnoow_id:" . $id . "}) DELETE u;";
        $query = new Query($this->client, $queryString);

        try{
            $result = $query->getResultSet();
        }catch (\Exception $e){
            throw $e;
        }

        return $this->parseResultSet($result);
    }

    public function getAll(){

        $queryString = "MATCH (u:User) RETURN u;";
        $query = new Query($this->client, $queryString);

        try{
            $result = $query->getResultSet();
        }catch (\Exception $e){
            throw $e;
        }

        return $this->parseResultSet($result);

    }
    
    public function getById($id = null){

        $queryString = "MATCH (u:User { qnoow_id : " . $id . "}) RETURN u;";
        $query = new Query($this->client, $queryString);

        try{
            $result = $query->getResultSet();
        }catch (\Exception $e){
            throw $e;
        }

        return $this->parseResultSet($result);

    }

    public function getMatchingByQuestionsByIds($id1, $id2){

        $response = array();

        //Check that both users have at least one question in common
        $check =
            "MATCH
                (u1:User {qnoow_id: '" . $id1 . "'}),
                (u2:User {qnoow_id: '" . $id2 . "'})
            OPTIONAL MATCH
                (u1)-[:ANSWERS]->(a1:Answer)-[:IS_ANSWER_OF]->(commonquestion:Question)<-[:IS_ANSWER_OF]-(a2:Answer)<-[:ANSWERS]-(u2)
            RETURN
                count(commonquestion) AS n;";

        //Create the Neo4j query object
        $checkQuery = new Query(
            $this->client,
            $check
        );

        try{
            $checkResult = $checkQuery->getResultSet();
        }catch (\Exception $e){
            throw $e;
        }

        $checkValue = 0;
        foreach($checkResult as $checkRow){
            $checkValue = $checkRow['n'];
        }

        if ($checkValue > 0){

            //Construct the query string
            $query =
                "MATCH
                    (u1:User {qnoow_id: '" . $id1 . "'}),
                    (u2:User {qnoow_id: '" . $id2 . "'})
                OPTIONAL MATCH
                    (u1)-[:ACCEPTS]->(commonanswer1:Answer)<-[:ANSWERS]-(u2),
                    (commonanswer1)-[:IS_ANSWER_OF]->(commonquestion1)<-[r1:RATES]-(u1)
                OPTIONAL MATCH
                    (u2)-[:ACCEPTS]->(commonanswer2:Answer)<-[:ANSWERS]-(u1),
                    (commonanswer2)-[:IS_ANSWER_OF]->(commonquestion2)<-[r2:RATES]-(u2)
                OPTIONAL MATCH
                    (u1)-[:ANSWERS]->(:Answer)-[:IS_ANSWER_OF]->(commonquestion:Question)<-[:IS_ANSWER_OF]-(:Answer)<-[:ANSWERS]-(u2),
                    (u1)-[r3:RATES]->(commonquestion)<-[r4:RATES]-(u2)
                WITH
                    [n1 IN collect(distinct r1) |n1._rating] AS little1_elems,
                    [n2 IN collect(distinct r2) |n2._rating] AS little2_elems,
                    [n3 IN collect(distinct r3) |n3._rating] AS CIT1_elems,
                    [n4 IN collect(distinct r4) |n4._rating] AS CIT2_elems
                WITH
                    reduce(little1 = 0, n1 IN little1_elems | little1 + n1) AS little1,
                    reduce(little2 = 0, n2 IN little2_elems | little2 + n2) AS little2,
                    reduce(CIT1 = 0, n3 IN CIT1_elems | CIT1 + n3) AS CIT1,
                    reduce(CIT1 = 0, n4 IN CIT2_elems | CIT1 + n4) AS CIT2
                WITH
                    sqrt( (little1*1.0/CIT1) * (little2*1.0/CIT2) ) AS match_user1_user2
                MATCH
                    (u1:User {qnoow_id: '" . $id1 . "'}),
                    (u2:User {qnoow_id: '" . $id2 . "'})
                CREATE UNIQUE
                    (u1)-[m:MATCHES]-(u2) SET m.matching = match_user1_user2
                RETURN
                    m;";

            //Create the Neo4j query object
            $neoQuery = new Query(
                $this->client,
                $query
            );

            //Execute query and get the return
            try{
                $result = $query->getResultSet();
            }catch (\Exception $e){
                throw $e;
            }

            foreach ($result as $row) {
                $response['matching'] = $row['m']->getProperty('matching');
            }

        }
        else{
            $response['matching'] = 0;
        }

        return $response;

    }

    private function parseResultSet($resultSet){

        $users = array();

        foreach ($resultSet as $row) {
            $user = array(
                'qnoow_id' => $row['u']->getProperty('qnoow_id'),
                'username' => $row['u']->getProperty('username'),
                'email' => $row['u']->getProperty('email'),
            );
            $users[] = $user;
        }

        return $users;

    }

} 