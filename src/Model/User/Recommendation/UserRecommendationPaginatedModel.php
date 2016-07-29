<?php

namespace Model\User\Recommendation;

use Everyman\Neo4j\Query\ResultSet;
use Model\User\GhostUser\GhostUserManager;

class UserRecommendationPaginatedModel extends AbstractUserPaginatedModel
{
    const USER_SAFETY_LIMIT = 5000;

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

        $parameters = array(
            'offset' => (integer)$offset,
            'limit' => (integer)$limit,
            'userId' => (integer)$id
        );

        $orderQuery = '  similarity DESC, matching_questions DESC, id ';
        if (isset($filters['order']) && $filters['order'] == 'questions') {
            $orderQuery = ' matching_questions DESC, similarity DESC, id ';
        }

        $filters = $this->profileFilterModel->splitFilters($filters);

        $profileFilters = $this->getProfileFilters($filters['profileFilters']);
        $userFilters = $this->getUserFilters($filters['userFilters']);

        $return = array('items' => array());

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters($parameters);

        $qb->match('(u:User {qnoow_id: {userId}})-[:MATCHES|SIMILARITY]-(anyUser:User)')
            ->where('u <> anyUser', 'NOT (anyUser:' . GhostUserManager::LABEL_GHOST_USER . ')')
            ->with('u', 'anyUser')
            ->limit(self::USER_SAFETY_LIMIT)
            ->with('u', 'anyUser')
            ->where($userFilters['conditions'])
            ->optionalMatch('(u)-[like:LIKES]->(anyUser)')
            ->with('u', 'anyUser', '(CASE WHEN like IS NOT NULL THEN 1 ELSE 0 END) AS like')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->with('u', 'anyUser', 'like', '(CASE WHEN EXISTS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with('u', 'anyUser', 'like', 'matching_questions', '(CASE WHEN EXISTS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity')
            ->where('(matching_questions > 0 OR similarity > 0)')
            ->with('u', 'anyUser', 'like', 'matching_questions', 'similarity')
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('u, anyUser, like, matching_questions, similarity, p, l');
        $qb->where( $profileFilters['conditions'])
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

        $response = $this->buildResponseFromResult($result);
        $return['items'] = array_merge($return['items'], $response['items']);

        $needContent = $this->needMoreContent($limit, $return);
        if ($needContent) {

            $foreign = 0;
            if (isset($filters['foreign'])) {
                $foreign = $filters['foreign'];
            }

            $foreignResult = $this->getForeignContent($filters, $needContent, $foreign);
            $return['items'] = array_merge($return['items'], $foreignResult['items']);
            $return['newForeign'] = $foreignResult['foreign'];
        }
        //Works with ContentPaginator (accepts $result), not Paginator (accepts $result['items'])
        return $return;
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
            ->where('u <> anyUser', 'NOT (anyUser:' . GhostUserManager::LABEL_GHOST_USER . ')')
            ->with('u', 'anyUser')
            ->limit(self::USER_SAFETY_LIMIT)
            ->with('u', 'anyUser')
            ->where($userFilters['conditions'])
            ->optionalMatch('(u)-[like:LIKES]->(anyUser)')
            ->with('u', 'anyUser', '(CASE WHEN like IS NOT NULL THEN 1 ELSE 0 END) AS like')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->with('u', 'anyUser', 'like', '(CASE WHEN EXISTS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with('u', 'anyUser', 'like', 'matching_questions', '(CASE WHEN EXISTS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity')
            ->where('(matching_questions > 0 OR similarity > 0)')
            ->with('u', 'anyUser', 'like', 'matching_questions', 'similarity')
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('u, anyUser, like, matching_questions, similarity, p, l');
        $qb->where( $profileFilters['conditions'])
            ->with('u', 'anyUser', 'like', 'matching_questions', 'similarity', 'p', 'l');

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

    public function buildResponseFromResult(ResultSet $result) {
        return array('items' => $this->buildUserRecommendations($result));
    }

    /**
     * @param $limit int
     * @param $response array
     * @return int
     */
    protected function needMoreContent($limit, $response)
    {
        $moreContent = $limit - count($response['items']);
        if ($moreContent <= 0) {
            return 0;
        }

        return $moreContent;
    }

    /**
     * @param $filters
     * @param $limit
     * @param $foreign
     * @return array (items, foreign = # of links database searched, -1 if total)
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getForeignContent($filters, $limit, $foreign)
    {
        $id = $filters['id'];
        $condition = "MATCH (u:User{qnoow_id:$id}) WHERE NOT (u)-[:SIMILARITY|:MATCHES]-(anyUser)";

        $items = $this->getUsersByPopularity($filters, $limit, $foreign, $condition);

        $return = array('items' => array_slice($items, 0, $limit) );
        $return['foreign'] = $foreign + count($return['items']);

        return $return;
    }
} 