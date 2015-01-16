<?php

namespace Model\User\Recommendation;

use Model\User\ProfileModel;
use Paginator\PaginatedInterface;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class UserRecommendationPaginatedModel implements PaginatedInterface
{

    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @var ProfileModel
     */
    protected $profileModel;

    /**
     * @param \Everyman\Neo4j\Client $client
     */
    public function __construct(Client $client, ProfileModel $profileModel)
    {
        $this->client = $client;
        $this->profileModel = $profileModel;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $hasId = isset($filters['id']);
        $hasProfileFilters = isset($filters['profileFilters']);

        return $hasId && $hasProfileFilters;
    }

    /**
     * Slices the query according to $offset, and $limit.
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @throws \Exception
     * @return array
     */
    public function slice(array $filters, $offset, $limit)
    {
        $id = $filters['id'];
        $response = array();

        $params = array(
            'UserId' => (integer)$id,
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        $profileFilters = $this->getProfileFilters($filters['profileFilters']);

        $orderQuery = ' ORDER BY matching_questions DESC ';
        if (isset($filters['order']) && $filters['order'] == 'content') {
            $orderQuery = ' ORDER BY matching_content DESC ';
        }

        $query = "
            MATCH (u:User {qnoow_id: " . $id . "})
            MATCH (u)-[r:MATCHES]-(anyUser:User)
            MATCH (anyUser)<-[:PROFILE_OF]-(p:Profile)
            WHERE (r.matching_questions > 0 OR r.matching_content > 0)";

        if ($profileFilters) {
            $query .= "\n" . implode("\n", $profileFilters);
        }

        $query .= "
            RETURN
            anyUser.qnoow_id AS id,
            anyUser.username AS username,
            CASE r.matching_questions IS NULL WHEN true THEN 0 ELSE r.matching_questions END as matching_questions,
            CASE r.matching_content IS NULL WHEN true THEN 0 ELSE r.matching_content END as matching_content";
        $query .= $orderQuery;
        $query .= "
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
                $user['id'] = $row['id'];
                $user['username'] = $row['username'];
                $user['matching_questions'] = $row['matching_questions'];
                $user['matching_content'] = $row['matching_content'];

                $response[] = $user;
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $response;
    }

    /**
     * Counts the total results from queryset.
     * @param array $filters
     * @throws \Exception
     * @return int
     */
    public function countTotal(array $filters)
    {
        $id = $filters['id'];
        $count = 0;

        $params = array(
            'UserId' => (integer)$id,
        );

        $profileFilters = $this->getProfileFilters($filters['profileFilters']);

        $query = "
            MATCH (u:User {qnoow_id: " . $id . "})
            MATCH (u)-[r:MATCHES]-(anyUser:User)
            MATCH (anyUser)<-[:PROFILE_OF]-(p:Profile)
            WHERE r.matching_questions > 0 OR r.matching_content > 0";

        if ($profileFilters) {
            $query .= "\n" . implode("\n", $profileFilters);
        }

        $query .= "
            RETURN
            count(distinct anyUser) as total;
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
                $count = $row['total'];
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $count;
    }

    protected function getProfileFilters(array $filters)
    {
        $profileFilters = array();

        foreach ($this->profileModel->getFilters() as $name => $filter) {
            if (isset($filters[$name])) {
                $value = $filters[$name];
                switch ($filter['type']) {
                    case 'string':
                        $profileFilters[] = "AND p.$name =~ '(?i).*$value.*'";
                        break;
                    case 'integer':
                        $min = $value['min'];
                        $max = $value['max'];
                        $profileFilters[] = "AND ('$min' <= p.$name AND p.$name <= '$max')";
                        break;
                    case 'date':

                        break;
                    case 'birthday':
                        $min = $value['min'];
                        $max = $value['max'];
                        $profileFilters[] = "AND ('$min' <= p.$name AND p.$name <= '$max')";
                        break;
                    case 'boolean':
                        $profileFilters[] = "AND p.$name = true";
                        break;
                    case 'choice':
                        $profileLabelName = ucfirst($name);
                        $value = implode("', '", $value);
                        $profileFilters[] = "MATCH (p)<-[:OPTION_OF]-(option:$profileLabelName) WHERE option.id IN ['$value']";
                        break;
                    case 'location':
                        break;
                }
            }
        }

        return $profileFilters;
    }
} 