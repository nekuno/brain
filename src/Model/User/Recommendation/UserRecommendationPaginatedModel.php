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

        $orderQuery = ' matching_questions DESC, similarity DESC, id ';
        if (isset($filters['userFilters']['order']) && $filters['userFilters']['order'] == 'similarity') {
            $orderQuery = '  similarity DESC, matching_questions DESC, id ';
            unset($filters['userFilters']['order']);
        }

        $filters = $this->profileFilterModel->splitFilters($filters);

        $profileFilters = $this->getProfileFilters($filters['profileFilters']);
        $userFilters = $this->getUserFilters($filters['userFilters']);

        $return = array('items' => array());

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters($parameters);

        $qb->match('(u:User {qnoow_id: {userId}})-[:MATCHES|:SIMILARITY]-(anyUser:User)')
            ->where('u <> anyUser', 'NOT (anyUser:' . GhostUserManager::LABEL_GHOST_USER . ')', 'NOT (u)-[:DISLIKES|:IGNORES]->(anyUser)')
            ->with('DISTINCT anyUser', 'u')
            ->limit(self::USER_SAFETY_LIMIT)
            ->with('u', 'anyUser')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->with('u', 'anyUser', '(CASE WHEN EXISTS(m.matching_questions) THEN m.matching_questions ELSE 0.01 END) AS matching_questions')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with('u', 'anyUser', 'matching_questions', '(CASE WHEN EXISTS(s.similarity) THEN s.similarity ELSE 0.01 END) AS similarity')
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('u, anyUser, matching_questions, similarity, p, l');
        $qb->where($profileFilters['conditions'])
            ->with('u', 'anyUser', 'matching_questions', 'similarity', 'p', 'l');
        $qb->where( $userFilters['conditions'])
            ->with('u', 'anyUser', 'matching_questions', 'similarity', 'p', 'l');

        foreach ($profileFilters['matches'] as $match) {
            $qb->match($match);
        }
        foreach ($userFilters['matches'] as $match) {
            $qb->match($match);
        }

        $qb->with('anyUser, u, matching_questions, similarity, p, l')
            ->optionalMatch('(u)-[likes:LIKES]->(anyUser)')
            ->with('anyUser, u, matching_questions, similarity, p, l, (CASE WHEN likes IS NULL THEN 0 ELSE 1 END) AS like')
            ->optionalMatch('(p)<-[optionOf:OPTION_OF]-(option:ProfileOption)')
            ->optionalMatch('(p)-[tagged:TAGGED]-(tag:ProfileTag)');

        $qb->returns(
            'anyUser.qnoow_id AS id,
             anyUser.username AS username,
             anyUser.photo AS photo,
             p.birthday AS birthday,
             p AS profile,
             collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options,
             collect(distinct {tag: tag, tagged: tagged}) AS tags,
             l AS location,
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

        $needContent = $this->needMoreContent($limit, $return);
        if ($needContent) {
            $ignored = 0;
            if (isset($filters['ignored'])) {
                $ignored = $filters['ignored'];
            }

            $ignoredResult = $this->getIgnoredContent($filters, $needContent, $ignored);
            $return['items'] = array_merge($return['items'], $ignoredResult['items']);
            $return['newIgnored'] = $ignoredResult['ignored'];
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

        $qb->match('(u:User {qnoow_id: {userId}}), (anyUser:User)')
            ->where('u <> anyUser', 'NOT (anyUser:' . GhostUserManager::LABEL_GHOST_USER . ')', 'NOT (u)-[:LIKES|:DISLIKES]->(anyUser)')
            ->with('DISTINCT anyUser as anyUser', 'u')
            ->limit(self::USER_SAFETY_LIMIT)
            ->with('u', 'anyUser')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->with('u', 'anyUser', '(CASE WHEN EXISTS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with('u', 'anyUser', 'matching_questions', '(CASE WHEN EXISTS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity')
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('u, anyUser, matching_questions, similarity, p, l');
        $qb->where($profileFilters['conditions'])
            ->with('u', 'anyUser', 'matching_questions', 'similarity', 'p', 'l');
        $qb->where( $userFilters['conditions'])
            ->with('u', 'anyUser', 'matching_questions', 'similarity', 'p', 'l');

        foreach ($profileFilters['matches'] as $match) {
            $qb->match($match);
        }
        foreach ($userFilters['matches'] as $match) {
            $qb->match($match);
        }

        $qb->returns('COUNT(anyUser) as total');
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
    }

    public function buildResponseFromResult(ResultSet $result)
    {
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
        $condition = "MATCH (u:User{qnoow_id:$id}) WHERE NOT (u)-[:LIKES|:DISLIKES|:IGNORES]->(anyUser) AND NOT (u)-[:MATCHES|:SIMILARITY]-(anyUser)";

        $items = $this->getUsersByPopularity($filters, $foreign, $limit, $condition);

        $return = array('items' => array_slice($items, 0, $limit));
        $return['foreign'] = $foreign + count($return['items']);

        return $return;
    }

    /**
     * @param $filters
     * @param $limit
     * @param $ignored
     * @return array (items, foreign = # of links database searched, -1 if total)
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getIgnoredContent($filters, $limit, $ignored)
    {
        $id = $filters['id'];
        $condition = "MATCH (:User{qnoow_id:$id})-[:IGNORES]->(anyUser)";
        $items = $this->getUsersByPopularity($filters, $ignored, $limit, $condition);

        $return = array('items' => array_slice($items, 0, $limit));
        $return['ignored'] = $ignored + count($return['items']);

        return $return;
    }
} 