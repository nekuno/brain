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

    public function update(array $user = array())
    {
        // TODO: do your magic here
    }

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

    public function getMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2)
    {

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

        try {
            $checkResult = $checkQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        $checkValue = 0;
        foreach ($checkResult as $checkRow) {
            $checkValue = $checkRow['n'];
        }

        if ($checkValue > 0) {

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
                    [n1 IN collect(distinct r1) |n1.rating] AS little1_elems,
                    [n2 IN collect(distinct r2) |n2.rating] AS little2_elems,
                    [n3 IN collect(distinct r3) |n3.rating] AS CIT1_elems,
                    [n4 IN collect(distinct r4) |n4.rating] AS CIT2_elems
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
                    (u1)-[m:MATCHES]-(u2)
                SET
                    m.questionMatching = match_user1_user2
                RETURN
                    m;";


            //Create the Neo4j query object
            $neoQuery = new Query(
                $this->client,
                $query
            );

            //Execute query and get the return
            try {
                $result = $query->getResultSet();
            } catch (\Exception $e) {
                throw $e;
            }

            foreach ($result as $row) {
                $response['matching'] = $row['m']->getProperty('questionMatching');
            }

        } else {
            $response['matching'] = 0;
        }

        return $response;

    }

    public function getMatchingBetweenTwoUsersBasedOnSharedContent($id1, $id2)
    {

        $response = array();

        //Check that both users have at least one url in common
        $check =
            "MATCH
                (u1:User {qnoow_id: '" . $id1 . "'}),
                (u2:User {qnoow_id: '" . $id2 . "'})
            OPTIONAL MATCH
                (u1)-[:LIKES]->(l:Link)-<-[:LIKES]-(u2)
            OPTIONAL MATCH
                (u1)-[:DISLIKES]->(d:Link)-<-[:DISLIKES]-(u2)
            RETURN
                count(l) AS l,
                count(d) AS d;";

        //Create the Neo4j query object
        $checkQuery = new Query(
            $this->client,
            $check
        );

        try {
            $checkResult = $checkQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        $checkValueLikes    = 0;
        $checkValueDislikes = 0;
        foreach ($checkResult as $checkRow) {
            $checkValueLikes    = $checkRow['l'];
            $checkValueDislikes = $checkRow['d'];
        }

        if ($checkValueLikes > 0 || $checkValueDislikes > 0) {

            //Construct the query string if both users have at least one link in common


            /*
                 *  QUERY DEL DIVIDENDO:
                 *
    MATCH
    (u:User)-[r:LIKES|DISLIKES]->(l:Link)
    WITH
    l, count(r) AS num_likes_dislikes
    ORDER BY num_likes_dislikes DESC
    WITH
    collect(num_likes_dislikes)[0] AS max_likes_dislikes

    MATCH
    (u1:User {qnoow_id: 'user-test1'}),
    (u2:User {qnoow_id: 'user-test2'})
    OPTIONAL MATCH
    (u1)-[:LIKES]->(commonLikes:Link)<-[:LIKES]-(u2)
    OPTIONAL MATCH
    (u1)-[:DISLIKES]->(commonDislikes:Link)<-[:DISLIKES]-(u2)
    WITH
    max_likes_dislikes,
    collect(distinct commonLikes) AS cl,
    collect(distinct commonDislikes) As cd
    MATCH
    (n)
    WHERE
    n IN cl OR n IN cd
    WITH
    n AS common,
    max_likes_dislikes AS max_popul

    MATCH
    (anyUser1)-[r1:LIKES|DISLIKES]->(common)
    WITH
    common,
    count(distinct r1) AS popul_comm,
    max_popul
    WITH
    reduce(num = 0.0, c IN collect(popul_comm) | num + (1 - (c*1.0 / max_popul))^3 ) as dividend,
    max_popul
    RETURN
    dividend;

                 * QUERY DEL DIVISOR 1:

    MATCH
    (u:User)-[r:LIKES|DISLIKES]->(l:Link)
    WITH
    l, count(r) AS num_likes_dislikes
    ORDER BY num_likes_dislikes DESC
    WITH
    collect(num_likes_dislikes)[0] AS max_likes_dislikes

    MATCH
    (u1:User {qnoow_id: 'user-test1'}),
    (u2:User {qnoow_id: 'user-test2'})
    OPTIONAL MATCH
    (u1)-[:LIKES]->(commonLikes:Link)<-[:LIKES]-(u2)
    OPTIONAL MATCH
    (u1)-[:DISLIKES]->(commonDislikes:Link)<-[:DISLIKES]-(u2)
    WITH
    max_likes_dislikes,
    collect(distinct commonLikes) AS cl,
    collect(distinct commonDislikes) As cd
    MATCH
    (n)
    WHERE
    n IN cl OR n IN cd
    WITH
    n AS common,
    max_likes_dislikes AS max_popul

    MATCH
    (u1:User {qnoow_id: 'user-test1'})
    OPTIONAL MATCH
    (u1)-[:LIKES|DISLIKES]->(contentU1)
    OPTIONAL MATCH
    (anyUser)-[r:LIKES|DISLIKES]->(contentU1)
    WITH
    collect(distinct contentU1) AS c1,
    collect(common) AS common,
    max_popul,
    r
    MATCH
    (n)
    WHERE
    n IN c1 AND NOT(n IN common)
    WITH
    n as not_common_u1,
    count(distinct r) AS popul_not_common_u1,
    max_popul
    WITH
    reduce(num = 0.0, c IN collect(popul_not_common_u1) | num + ( (c*1.0 / max_popul))^3 ) as divisor1,
    max_popul

    RETURN
    divisor1;

                 * QUERY DEL DIVISOR 2:

    MATCH
    (u:User)-[r:LIKES|DISLIKES]->(l:Link)
    WITH
    l, count(r) AS num_likes_dislikes
    ORDER BY num_likes_dislikes DESC
    WITH
    collect(num_likes_dislikes)[0] AS max_likes_dislikes

    MATCH
    (u1:User {qnoow_id: 'user-test1'}),
    (u2:User {qnoow_id: 'user-test2'})
    OPTIONAL MATCH
    (u1)-[:LIKES]->(commonLikes:Link)<-[:LIKES]-(u2)
    OPTIONAL MATCH
    (u1)-[:DISLIKES]->(commonDislikes:Link)<-[:DISLIKES]-(u2)
    WITH
    max_likes_dislikes,
    collect(distinct commonLikes) AS cl,
    collect(distinct commonDislikes) As cd
    MATCH
    (n)
    WHERE
    n IN cl OR n IN cd
    WITH
    n AS common,
    max_likes_dislikes AS max_popul

    MATCH
    (u2:User {qnoow_id: 'user-test2'})
    OPTIONAL MATCH
    (u2)-[:LIKES|DISLIKES]->(contentU2)
    OPTIONAL MATCH
    (anyUser)-[r:LIKES|DISLIKES]->(contentU2)
    WITH
    collect(distinct contentU2) AS c2,
    collect(common) AS common,
    max_popul,
    r
    MATCH
    (n)
    WHERE
    n IN c2 AND NOT(n IN common)
    WITH
    n as not_common_u2,
    count(distinct r) AS popul_not_common_u2,
    max_popul
    WITH
    reduce(num = 0.0, c IN collect(popul_not_common_u2) | num + ( (c*1.0 / max_popul))^3 ) as divisor2,
    max_popul
    RETURN
    divisor2;

                TODO: borrar $query y construir las correspondientes $query1, $query2 y $query3
                TODO: a la hora de construir las queries, sustituir "user-testX" por la variable (string) $idX
                TODO: guardar los resultados devueltos por las tres queries en variables de PHP
                TODO: matching = sqrt( dividendo^2 / ( (dividendo + divisor1)*(dividendo + divisor2) ) )

     */

            $query =
                "MATCH
                    (u:User)-[r:LIKES|DISLIKES]->(l:Link)
                WITH
                    l, count(r) AS num_likes_dislikes
                ORDER BY num_likes_dislikes DESC
                WITH
                    collect(num_likes_dislikes)[0] AS max_likes_dislikes
                MATCH
                    (u1:User {qnoow_id: 'user-test1'}),
                    (u2:User {qnoow_id: 'user-test2'})
                OPTIONAL MATCH
                    (u1)-[:LIKES]->(commonLikes:Link)<-[:LIKES]-(u2)
                OPTIONAL MATCH
                    (u1)-[:DISLIKES]->(commonDislikes:Link)<-[:DISLIKES]-(u2)
                OPTIONAL MATCH
                    (u1)-[:LIKES|DISLIKES]->(contentU1),
                    (u2)-[:LIKES|DISLIKES]->(contentU2)
                OPTIONAL MATCH
                    (anyUser1)-[:LIKES|DISLIKES]->(commonLikes)
                OPTIONAL MATCH
                    (anyUser2)-[:LIKES|DISLIKES]->(commonDislikes)
                WITH
                    ( count(distinct commonLikes) + count(distinct commonDislikes) )*1.0 / ( (count(distinct contentU1) + count(distinct contentU2) - count(distinct commonLikes) - count(distinct commonDislikes) ) ) AS ratio,
                    ( ( count(distinct anyUser1) + count(distinct anyUser2) )*1.0 / ( count(distinct commonLikes) + count(distinct commonDislikes) ) ) / max_likes_dislikes as popul
                WITH
                    (ratio + ((1 - popul)^(1.0/3)) ) /2 AS match_content
                MATCH
                    (u1:User {qnoow_id: '" . $id1 . "'}),
                    (u2:User {qnoow_id: '" . $id2 . "'})
                CREATE UNIQUE
                    (u1)-[m:MATCHES]-(u2)
                SET
                    m.contentMatching = match_user1_user2
                RETURN
                    m;";

            //Create the Neo4j query object
            $neoQuery = new Query(
                $this->client,
                $query
            );

            //Execute query and get the return
            try {
                $result = $query->getResultSet();
            } catch (\Exception $e) {
                throw $e;
            }

            foreach ($result as $row) {
                $response['matching'] = $row['m']->getProperty('contentMatching');
            }

        } else {
            $response['matching'] = 0;
        }

        return $response;

    }

    /**
    * Get top recommended users based on Answes to Questions
    *
    * @param    int     $id     id of the user
    * @return   array           ordered array of users
    */
    public function getUserRecommendationsBasedOnAnswers($id)
    {
        calculateAllMatchingsBasedOnAnswers($id);

        $response = array(
            1 => array(
                'id' => '1',
                'matching' => '0.89'
                )
            );

        return $response;
    }

    /**
    * Get top recommended users based on Answes to Questions
    *
    * @param    int     $id     id of the user
    * @return   array           ordered array of users
    */
    public function getUserRecommendationsBasedOnSharedContent($id)
    {
        calculateAllMatchingsBasedOnContent($id);

        $response = array(
            1 => array(
                'id' => '1',
                'matching' => '0.89'
                )
            );

        return $response;
    }

    /**
    * Get top recommended users based on Answes to Questions
    *
    * @param    int     $id     id of the user
    * @return   array           ordered array of contents
    */
    public function getContentRecommendations($id)
    {
        $response = array(
            1 => array(
                'title' => 'Google',
                'url' => 'http://google.com',
                'description' => 'Web search engine'
                )
            );

        return $response;
    }

    /**
     * Calculate matchings of the user with any other user, based on Answers to Questions
     *
     * @param   int $id id of the user
     */
    public function calculateAllMatchingsBasedOnAnswers($id){

    }

    /**
     * Calculate matchings of the user with any other user, based on shared Content
     *
     * @param   int $id id of the user
     */
    public function calculateAllMatchingsBasedOnContent($id){

    }

} 
