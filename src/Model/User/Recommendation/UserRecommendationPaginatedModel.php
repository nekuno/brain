<?php

namespace Model\User\Recommendation;

use Everyman\Neo4j\Cypher\Query;
use Model\Neo4j\GraphManager;
use Model\Neo4j\QueryBuilder;
use Model\User\GhostUser\GhostUserManager;
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
        $notMultipleGroups = !(isset($filters['userFilters']['groups']) && count($filters['userFilters']['groups']) > 1);

        return $hasId && $hasProfileFilters && $notMultipleGroups;
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
        $groups = isset($filters['userFilters']['groups']) ? $filters['userFilters']['groups'] : array();
        $groups = array_map(
            function ($i) {
                return (integer)$i;
            },
            $groups
        );

        $response = array();

        $parameters = array(
            'offset' => (integer)$offset,
            'limit' => (integer)$limit,
            'userId' => (integer)$id
        );

        $profileFilters = $this->getProfileFilters($filters['profileFilters']);
        $orderQuery = '  similarity DESC, matching_questions DESC ';
        if (isset($filters['order']) && $filters['order'] == 'questions') {
            $orderQuery = ' matching_questions DESC, similarity DESC ';
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters($parameters);

        $qb->match('(u:User {qnoow_id: {userId}})-[:MATCHES|SIMILARITY]-(anyUser:User)')
            ->where('u <> anyUser', 'NOT (anyUser:'.GhostUserManager::LABEL_GHOST_USER.')')
            ->optionalMatch('(u)-[like:LIKES]-(anyUser)')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with(
                'u, anyUser,
                (CASE WHEN like IS NOT NULL THEN 1 ELSE 0 END) AS like,
                (CASE WHEN HAS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions,
                (CASE WHEN HAS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity'
            )
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('u, anyUser, like, matching_questions, similarity, p, l');
        $qb->where(
            array_merge(
                array('(matching_questions > 0 OR similarity > 0)'),
                $profileFilters['conditions']
            )
        )
            ->with('u', 'anyUser', 'like', 'matching_questions', 'similarity', 'p', 'l');

        foreach ($profileFilters['matches'] as $match) {
            $qb->match($match);
        }

        if ($groups) {
            $qb->match('(anyUser)-[:BELONGS_TO]->(g:Group)')
                ->where('id(g) IN { groups }')
                ->setParameter('groups', $groups);
        }

        $qb->returns(
            'DISTINCT anyUser.qnoow_id AS id,
                    anyUser.username AS username,
                    anyUser.picture AS picture,
                    p.birthday AS birthday,
                    l.locality + ", " + l.country AS location,
                    matching_questions,
                    similarity,
                    like'
        )
            ->orderBy($orderQuery)
            ->skip('{ offset }')
            ->limit('{ limit }');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {

            $age = null;
            if ($row['birthday']) {
                $date = new \DateTime($row['birthday']);
                $now = new \DateTime();
                $interval = $now->diff($date);
                $age = $interval->y;
            }

            $user = array(
                'id' => $row['id'],
                'username' => $row['username'],
                'picture' => $row['picture'],
                'matching' => $row['matching_questions'],
                'similarity' => $row['similarity'],
                'age' => $age,
                'location' => $row['location'],
                'like' => $row['like'],
            );

            $response[] = $user;
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

        $groups = isset($filters['userFilters']['groups']) ? $filters['userFilters']['groups'] : array();
        $groups = array_map(
            function ($i) {
                return (integer)$i;
            },
            $groups
        );

        $profileFilters = $this->getProfileFilters($filters['profileFilters']);

        $qb = $this->gm->createQueryBuilder();

        $parameters = array('userId' => (integer)$id);

        $qb->setParameters($parameters);

        $qb->match('(u:User {qnoow_id: {userId}})-[:MATCHES|SIMILARITY]-(anyUser:User)')
            ->where('u <> anyUser')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with(
                'u, anyUser,
            (CASE WHEN HAS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions,
            (CASE WHEN HAS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity'
            )
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('u, anyUser, matching_questions, similarity, p, l');
        $qb->where(
            array_merge(
                array('(matching_questions > 0 OR similarity > 0)'),
                $profileFilters['conditions']
            )
        )
            ->with('u', 'anyUser', 'matching_questions', 'similarity', 'p', 'l');

        foreach ($profileFilters['matches'] as $match) {
            $qb->match($match);
        }

        if ($groups) {
            $qb->match('(anyUser)-[:BELONGS_TO]->(g:Group)')
                ->where('id(g) IN { groups }')
                ->setParameter('groups', $groups);
        }

        $qb->returns('COUNT(DISTINCT anyUser) as total');
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function getProfileFilters(array $filters)
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
                    case 'location':
                        $distance = (int)$value['distance'];
                        $latitude = (float)$value['location']['latitude'];
                        $longitude = (float)$value['location']['longitude'];
                        $conditions[] = "(NOT l IS NULL AND has(l.latitude) AND has(l.longitude) AND
                        " . $distance . " >= toInt(6371 * acos( cos( radians(" . $latitude . ") ) * cos( radians(l.latitude) ) * cos( radians(l.longitude) - radians(" . $longitude . ") ) + sin( radians(" . $latitude . ") ) * sin( radians(l.latitude) ) )))";
                        break;
                    case 'boolean':
                        $conditions[] = "p.$name = true";
                        break;
                    case 'choice':
                        $profileLabelName = ucfirst($name);
                        $value = implode("', '", $value);
                        $matches[] = "(p)<-[:OPTION_OF]-(option$name:$profileLabelName) WHERE option$name.id IN ['$value']";
                        break;
                    case 'double_choice':
                        $profileLabelName = ucfirst($name);
                        $value = implode("', '", $value);
                        $matches[] = "(p)<-[:OPTION_OF]-(option$name:$profileLabelName) WHERE option$name.id IN ['$value']";
                        break;
                    case 'tags':
                        $tagLabelName = ucfirst($name);
                        $matches[] = "(p)<-[:TAGGED]-(tag$name:$tagLabelName) WHERE tag$name.name = '$value'";
                        break;
                }
            }
        }

        return array(
            'conditions' => $conditions,
            'matches' => $matches
        );
    }

} 