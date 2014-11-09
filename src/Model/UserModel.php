<?php

namespace Model;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Model\User\UserStatusModel;
use Paginator\PaginatedInterface;

/**
 * Class UserModel
 *
 * @package Model
 */
class UserModel implements PaginatedInterface
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
        if (!isset($user['email'])) {
            $user['email'] = '';
        }

        $query = new Query(
            $this->client,
            "CREATE (u:User {
                status: '" . UserStatusModel::USER_STATUS_INCOMPLETE . "',
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
            $user = array(
                'qnoow_id' => $row['u']->getProperty('qnoow_id'),
                'username' => $row['u']->getProperty('username'),
                'email' => $row['u']->getProperty('email'),
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
        $query = new Query($this->client, $queryString);

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
        $query = new Query($this->client, $queryString);

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
        $query = new Query($this->client, $queryString);

        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->parseResultSet($result);

    }

    /**
     * @param $id
     * @return UserStatusModel
     */
    public function getStatus($id)
    {
        $queryString = "
             MATCH (u:User {qnoow_id: $id})
             OPTIONAL MATCH (u)-[:ANSWERS]->(a:Answer)
             OPTIONAL MATCH (u)-[:LIKES]->(l:Link)
             RETURN u.status AS status, COUNT(DISTINCT a) AS answerCount, COUNT(DISTINCT l) AS linkCount";
        $query = new Query($this->client, $queryString);

        $result = $query->getResultSet();

        /* @var $row \Everyman\Neo4j\Query\Row */
        $row = $result->current();
        $status = $row['status'];

        $status = new UserStatusModel($status, $row['answerCount'], $row['linkCount']);

        if ($status->getStatus() !== $status) {
            $newStatus = $status->getStatus();
            $queryString = "MATCH (u:User {qnoow_id: $id}) SET u.status = '$newStatus' RETURN u";
            $query = new Query($this->client, $queryString);
            $query->getResultSet();
        }

        return $status;
    }

    /**
     * @inheritdoc
     */
    public function validateFilters(array $filters)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function slice(array $filters, $offset, $limit)
    {
        $response = array();

        $params = array(
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        $profileQuery = "";
        if (isset($filters['profile'])) {
            $profileQuery = " MATCH (user)-[:PROFILE_OF]-(profile:Profile) ";
            if (isset($filters['profile']['zodiacSign'])) {
                $profileQuery .= " WHERE profile.zodiacSign = {zodiacSign} ";
                $params['zodiacSign'] = $filters['profile']['zodiacSign'];
            }
            if (isset($filters['profile']['gender'])) {
                $profileQuery .= "
                    MATCH
                    (profile)-[:OPTION_OF]-(gender:Gender)
                    WHERE id(gender) = {gender}
                ";
                $params['gender'] = (integer)$filters['profile']['gender'];
            }
            if (isset($filters['profile']['orientation'])) {
                $profileQuery .= "
                    MATCH
                    (profile)-[:OPTION_OF]-(orientation:Orientation)
                    WHERE id(orientation) = {orientation}
                ";
                $params['orientation'] = (integer)$filters['profile']['orientation'];
            }
        }

        $referenceUserQuery = "";
        $resultQuery = " RETURN user ";
        if (isset($filters['referenceUserId'])) {
            $params['referenceUserId'] = (integer)$filters['referenceUserId'];
            $referenceUserQuery = "
                MATCH
                (referenceUser:User)
                WHERE
                referenceUser.qnoow_id = {referenceUserId} AND
                user.qnoow_id <> {referenceUserId}
                OPTIONAL MATCH
                (user)-[match:MATCHES]-(referenceUser)
             ";
            $resultQuery .= ", match";
        }

        $query = "
            MATCH
            (user:User)
            WHERE
            user.status = 'complete'"
            . $profileQuery
            . $referenceUserQuery
            . $resultQuery
            . "
            SKIP {offset}
            LIMIT {limit}
            ;
         ";

        //Create the Neo4j query object
        $contentQuery = new Query(
            $this->client,
            $query,
            $params
        );

        //Execute query
        try {
            $result = $contentQuery->getResultSet();

            foreach ($result as $row) {
                $user = array();

                $user['id'] = $row['user']->getProperty('qnoow_id');
                $user['username'] = $row['content']->getProperty('username');
                $user['email'] = $row['content']->getProperty('email');
                $user['matching']['content'] = 0;
                $user['matching']['questions'] = 0;

                if (isset($row['match'])) {
                    $matchingByContent = $row['match']->getProperty('matching_content');
                    $matchingByQuestions = $row['match']->getProperty('matching_questions');
                    $user['matching']['content'] = null === $matchingByContent ? 0 : $matchingByContent;
                    $user['matching']['questions'] = null === $matchingByQuestions ? 0 : $matchingByQuestions;
                }

                $response[] = $user;
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function countTotal(array $filters)
    {
        $params = array();

        $queryWhere = " WHERE user.status = 'complete' ";
        if (isset($filters['referenceUserId'])) {
            $params['referenceUserId'] = (integer)$filters['referenceUserId'];
            $queryWhere .= " AND user.qnoow_id <> {referenceUserId} ";
        }

        if (isset($filters['profile'])) {
            //TODO: Profile filters
        }

        $query = "
            MATCH
            (user:User)
            " . $queryWhere . "
            RETURN
            count(user) as total
            ;
        ";

        //Create the Neo4j query object
        $contentQuery = new Query(
            $this->client,
            $query,
            $params
        );

        $count = 0;
        //Execute query
        try {
            $result = $contentQuery->getResultSet();

            foreach ($result as $row) {
                $count = $row['total'];
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $count;
    }
}
