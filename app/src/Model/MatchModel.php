<?php
/**
 * Created by PhpStorm.
 * User: zaski
 * Date: 6/12/14
 * Time: 5:31 PM
 */

namespace Model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;


class MatchModel {

    protected $client;

    public function __construct(Client $client){
        $this->client = $client;
    }

    public function create($id1, array $data = array() ){

        $id2 = $data['id'];

        //Construct the query string
        $query =
            "MATCH
                (u1:User {_username: '" . $id1 . "'}),
                (u2:User {_username: '" . $id2 . "'})
            MATCH
                (u1)-[:ACCEPTS]->(commonanswer1:Answer)<-[:ANSWERS]-(u2),
                (commonanswer1)-[:IS_ANSWER_OF]->(commonquestion1)<-[r1:RATES]-(u1)
            MATCH
                (u2)-[:ACCEPTS]->(commonanswer2:Anser)<-[:ANSWERS]-(u1),
                (commonanswer2)-[:IS_ANSWER_OF]->(commonquestion2)<-[r2:RATES]-(u2)
            MATCH
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
                (u1:User {_username: '" . $id1 . "'}),
                (u2:User {_username: '" . $id2 . "'})
            CREATE UNIQUE
                (u1)-[m:MATCHES]-(u2) SET m._matching = match_user1_user2
            RETURN
                m._matching AS matching;";

        //Create the Neo4j query object
        $neoQuery = new Query(
            $this->client,
            $query
        );

        //Execute query and get the return
        $result = $neoQuery->getResultSet();


        $response = array();
        $response['matching'] = $result['matching'];

        return $response;
    }

} 