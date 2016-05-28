<?php

namespace Model\User\Recommendation;

use Model\Neo4j\GraphManager;
use Model\User\GhostUser\GhostUserManager;
use Model\User\ProfileFilterModel;
use Model\User\UserFilterModel;

class UserRecommendationPaginatedModel extends AbstractUserPaginatedModel
{

    protected $userFilterModel;

    public function __construct(GraphManager $gm, ProfileFilterModel $profileFilterModel, UserFilterModel $userFilterModel)
    {
        parent::__construct($gm, $profileFilterModel);
        $this->userFilterModel = $userFilterModel;
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

        $orderQuery = '  similarity DESC, matching_questions DESC ';
        if (isset($filters['order']) && $filters['order'] == 'questions') {
            $orderQuery = ' matching_questions DESC, similarity DESC ';
        }

        $filters = $this->profileFilterModel->splitFilters($filters);

        $profileFilters = $this->getProfileFilters($filters['profileFilters']);
        $userFilters = $this->getUserFilters($filters['userFilters']);

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters($parameters);

        $qb->match('(u:User {qnoow_id: {userId}})-[:MATCHES|SIMILARITY]-(anyUser:User)')
            ->where('u <> anyUser', 'NOT (anyUser:' . GhostUserManager::LABEL_GHOST_USER . ')')
            ->optionalMatch('(u)-[like:LIKES]-(anyUser)')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with(
                'u, anyUser,
                (CASE WHEN like IS NOT NULL THEN 1 ELSE 0 END) AS like,
                (CASE WHEN HAS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions,
                (CASE WHEN HAS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity'
            )
            ->where($userFilters['conditions'])
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
        foreach ($userFilters['matches'] as $match) {
            $qb->match($match);
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

        $filters = $this->profileFilterModel->splitFilters($filters);

        $profileFilters = $this->getProfileFilters($filters['profileFilters']);
        $userFilters = $this->getUserFilters($filters['userFilters']);

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
            ->where($userFilters['conditions'])
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
        foreach ($userFilters['matches'] as $match) {
            $qb->match($match);
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
    protected function getUserFilters(array $filters)
    {
        $conditions = array();
        $matches = array();

        $userFilterMetadata = $this->getUserFilterMetadata();
        foreach ($userFilterMetadata as $name => $filter) {
            if (isset($filters[$name]) && !empty($filters[$name])) {
                $value = $filters[$name];
                switch ($name) {
                    case 'groups':
                        foreach ($value as $index => $groupId) {
                            $value[$index] = (int)$groupId;
                        }
                        $jsonValues = json_encode($value);
                        $matches[] = "(anyUser)-[:BELONGS_TO]->(group:Group) WHERE id(group) IN $jsonValues";
                        break;
                    case 'compatibility':
                        $valuePerOne = intval($value) / 100;
                        $conditions[] = "($valuePerOne <= matching_questions)";
                        break;
                    case 'similarity':
                        $valuePerOne = intval($value) / 100;
                        $conditions[] = "($valuePerOne <= similarity)";
                        break;
                }
            }
        }

        return array(
            'conditions' => $conditions,
            'matches' => $matches
        );
    }

    protected function getUserFilterMetadata(){
        return $this->userFilterModel->getFilters();
    }
} 