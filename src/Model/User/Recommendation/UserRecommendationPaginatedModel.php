<?php

namespace Model\User\Recommendation;

use Everyman\Neo4j\Cypher\Query;
use Model\Neo4j\GraphManager;
use Model\Neo4j\QueryBuilder;
use Model\User\ProfileModel;
use Paginator\PaginatedInterface;

class UserRecommendationPaginatedModel implements PaginatedInterface
{

    protected $gm;

    /**
     * @var ProfileModel
     */
    protected $profileModel;

    public function __construct(GraphManager $gm, ProfileModel $profileModel)
    {
        $this->gm = $gm;
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

        $parameters = array(
            'offset' => (integer)$offset,
            'limit' => (integer)$limit,
            'userId' => (integer)$id
        );

        // $profileFilters = $this->getProfileFilters($filters['profileFilters']);
        $profileFilters = $this->applyProfileFilters($filters['profileFilters']);
        $orderQuery = ' matching_questions DESC, similarity DESC ';
        if (isset($filters['order']) && $filters['order'] == 'content') {
            $orderQuery = ' similarity DESC, matching_questions DESC ';
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters($parameters);

        $qb->match('(u:User {qnoow_id: {userId}})-[:MATCHES|SIMILARITY]-(anyUser:User)')
            ->where('u <> anyUser')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with('u, anyUser,
                (CASE WHEN HAS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions,
                (CASE WHEN HAS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity')
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)')
            ->where(array_merge(
                array('(matching_questions > 0 OR similarity > 0)'),
                $profileFilters['conditions']))
            ->match($profileFilters['matches']);
        /*
                $filterQuery='';
                if ($profileFilters) {
                    $filterQuery .= "\n" . implode("\n", $profileFilters);
                }

                $qb->add(' ', $filterQuery);
        */


        $qb->returns('DISTINCT anyUser.qnoow_id AS id,
                    anyUser.username AS username,
                    matching_questions,
                    similarity')
            ->orderBy($orderQuery)
            ->skip('{offset}')
            ->limit('{limit}');
        $query = $qb->getQuery();


        /***************************************************
         * $orderQuery = ' ORDER BY matching_questions DESC, similarity DESC ';
         * if (isset($filters['order']) && $filters['order'] == 'content') {
         * $orderQuery = ' ORDER BY similarity DESC, matching_questions DESC ';
         * }
         *
         * $query = "MATCH (u:User {qnoow_id: $id})-[:MATCHES|SIMILARITY]-(anyUser:User)
         * WHERE u <> anyUser
         * OPTIONAL MATCH (u)-[m:MATCHES]-(anyUser)
         * OPTIONAL MATCH (u)-[s:SIMILARITY]-(anyUser)
         * WITH u, anyUser,
         * (CASE WHEN HAS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions,
         * (CASE WHEN HAS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity
         * MATCH (anyUser)<-[:PROFILE_OF]-(p:Profile)
         * WHERE (matching_questions > 0 OR similarity > 0) ";
         *
         * if ($profileFilters) {
         * $query .= "\n" . implode("\n", $profileFilters);
         * }
         *
         * $query .= "
         * RETURN
         * DISTINCT anyUser.qnoow_id AS id,
         * anyUser.username AS username,
         * matching_questions,
         * similarity";
         * $query .= $orderQuery;
         * $query .= "
         * SKIP {offset}
         * LIMIT {limit}";
         *
         * $query = $this->gm->createQuery($query, $parameters);
         * /**************************************************************/
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $user = array();
            $user['id'] = $row['id'];
            $user['username'] = $row['username'];
            $user['matching'] = $row['matching_questions'];
            $user['similarity'] = $row['similarity'];

            $response[] = $user;
        }
        /********************************************************************/
       // $response['query'] = $qb->getQuery()->getExecutableQuery();
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
/******************************************************************/
        $profileFilters=$this->applyProfileFilters($filters['profileFilters']);

        $qb=$this->gm->createQueryBuilder();

        $parameters=array('userId'=>(integer)$id);
        $qb->setParameters($parameters);

        $qb->match('(u:User {qnoow_id: {userId}})-[:MATCHES|SIMILARITY]-(anyUser:User)')
            ->where('u <> anyUser')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with('u, anyUser,
(CASE WHEN HAS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions,
(CASE WHEN HAS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity')
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)')
            ->where(array_merge(
                array('(matching_questions > 0 OR similarity > 0)'),
                $profileFilters['conditions']))
            ->match($profileFilters['matches'])
            ->returns('COUNT(DISTINCT anyUser) as total');
        $query=$qb->getQuery();
/**********************************************************
        $profileFilters = $this->getProfileFilters($filters['profileFilters']);

        $query = "MATCH (u:User {qnoow_id: $id})-[:MATCHES|SIMILARITY]-(anyUser:User)
WHERE u <> anyUser
OPTIONAL MATCH (u)-[m:MATCHES]-(anyUser)
OPTIONAL MATCH (u)-[s:SIMILARITY]-(anyUser)
WITH u, anyUser,
(CASE WHEN HAS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions,
(CASE WHEN HAS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity
MATCH (anyUser)<-[:PROFILE_OF]-(p:Profile)
WHERE (matching_questions > 0 OR similarity > 0) ";

        if ($profileFilters) {
            $query .= "\n" . implode("\n", $profileFilters);
        }

        $query .= "
RETURN COUNT(DISTINCT anyUser) as total;";

        $query = $this->gm->createQuery($query);
/****************************************************************************/
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
    }

    /**
     * @param array $filters
     * @param QueryBuilder $qb
     */
    protected function applyProfileFilters(array $filters)
    {
        $conditions = array();
        $matches = array();
        foreach ($this->profileModel->getFilters() as $name => $filter) {
            if (isset($filters[$name])) {
                $value = $filters[$name];
                switch ($filter['type']) {
                    case 'text':
                    case 'textarea':
                        $conditions[] = "p.$name =~ '(?i).*$value.*'";
                        break;
                    case 'integer':
                        $min = (integer)$value['min'];
                        $max = (integer)$value['max'];
                        $conditions[] = "($min <= p.$name AND p.$name <= $max)";
                        break;
                    case 'date':

                        break;
                    case 'birthday':
                        $min = $value['min'];
                        $max = $value['max'];
                        $conditions[] = "('$min' <= p.$name AND p.$name <= '$max')";
                        break;
                    case 'boolean':
                        $conditions[] = "p.$name = true";
                        break;
                    case 'choice':
                        $profileLabelName = ucfirst($name);
                        $value = implode("', '", $value);
                        $matches[] = "(p)<-[:OPTION_OF]-(option:$profileLabelName) WHERE option.id IN ['$value']";
                        break;
                    case 'tags':
                        $tagLabelName = ucfirst($name);
                        $matches[] = "(p)<-[:TAGGED]-(tag:$tagLabelName) WHERE tag.name = '$value'";
                        break;
                    case 'location':
                        break;
                }
            }
        }

        return array(
            'conditions' => $conditions,
            'matches' => $matches
        );
    }

    protected function getProfileFilters(array $filters)
    {
        $profileFilters = array();

        foreach ($this->profileModel->getFilters() as $name => $filter) {
            if (isset($filters[$name])) {
                $value = $filters[$name];
                switch ($filter['type']) {
                    case 'text':
                    case 'textarea':
                        $profileFilters[] = "AND p.$name =~ '(?i).*$value.*'";
                        break;
                    case 'integer':
                        $min = (integer)$value['min'];
                        $max = (integer)$value['max'];
                        $profileFilters[] = "AND ($min <= p.$name AND p.$name <= $max)";
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
                    case 'tags':
                        $tagLabelName = ucfirst($name);
                        $profileFilters[] = "MATCH (p)<-[:TAGGED]-(tag:$tagLabelName) WHERE tag.name = '$value'";
                        break;
                    case 'location':
                        break;
                }
            }
        }

        return $profileFilters;
    }
} 