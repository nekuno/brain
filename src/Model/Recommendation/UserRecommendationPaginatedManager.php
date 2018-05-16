<?php

namespace Model\Recommendation;

use Everyman\Neo4j\Query\ResultSet;

class UserRecommendationPaginatedManager extends AbstractUserRecommendationPaginatedManager
{
    const USER_SAFETY_LIMIT = 5000;

    /**
     * Slices the query according to $offset, and $limit.
     * @param array $filtersArray
     * @param int $offset
     * @param int $limit
     * @throws \Exception
     * @return array
     */
    public function slice(array $filtersArray, $offset, $limit)
    {
        $id = $filtersArray['id'];

        $orderQuery = ' matching_questions DESC, similarity DESC, id ';
        if (isset($filtersArray['userFilters']['order']) && $filtersArray['userFilters']['order'] == 'similarity') {
            $orderQuery = '  similarity DESC, matching_questions DESC, id ';
            unset($filtersArray['userFilters']['order']);
        }

        $appliedFilters = $this->applyFilters($filtersArray);

        $return = array('items' => array());

        $profile = $this->profileModel->getById($id);
        $objectives = $profile->get('objective') ?: array();

        $parameters = array(
            'offset' => (integer)$offset,
            'limit' => (integer)$limit,
            'userId' => (integer)$id,
            'objectives' => $objectives,
        );
        $qb = $this->gm->createQueryBuilder();
        $qb->setParameters($parameters);

        $qb->match('(u:User {qnoow_id: {userId}})-[:MATCHES|:SIMILARITY]-(anyUser:UserEnabled)')
            ->where('u <> anyUser', 'NOT (u)-[:DISLIKES|:IGNORES]->(anyUser)', 'NOT (u)<-[:BLOCKS]-(anyUser)')
            ->optionalMatch('(anyUser)<-[:PROFILE_OF]-(:Profile)<-[:OPTION_OF]-(o:Objective)')
            ->with('DISTINCT anyUser', 'u', 'CASE WHEN o IS NOT NULL AND ANY (x IN { objectives } WHERE x = o.id) THEN 1 ELSE 0 END as isCommonObjective')
            ->with('anyUser, u, CASE WHEN sum(isCommonObjective) > 0 THEN 1 ELSE 0 END AS hasCommonObjectives')
            ->limit(self::USER_SAFETY_LIMIT)
            ->with('u', 'anyUser', 'hasCommonObjectives')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->with('u', 'anyUser', 'hasCommonObjectives', '(CASE WHEN EXISTS(m.matching_questions) THEN m.matching_questions ELSE 0.01 END) AS matching_questions')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with('u', 'anyUser', 'hasCommonObjectives', 'matching_questions', '(CASE WHEN EXISTS(s.similarity) THEN s.similarity ELSE 0.01 END) AS similarity')
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('u, anyUser, hasCommonObjectives, matching_questions, similarity, p, l');
        $qb->where($appliedFilters['conditions'])
            ->with('u', 'anyUser', 'hasCommonObjectives', 'matching_questions', 'similarity', 'p', 'l');

        foreach ($appliedFilters['matches'] as $match) {
            $qb->match($match);
        }

        $qb->with('anyUser, u, hasCommonObjectives, matching_questions, similarity, p, l')
            ->optionalMatch('(u)-[likes:LIKES]->(anyUser)')
            ->with('anyUser, u, hasCommonObjectives, matching_questions, similarity, p, l, (CASE WHEN likes IS NULL THEN 0 ELSE 1 END) AS like')
            ->optionalMatch('(p)<-[optionOf:OPTION_OF]-(option:ProfileOption)')
            ->optionalMatch('(p)-[tagged:TAGGED]-(tag:ProfileTag)');

        $qb->returns(
            'anyUser.qnoow_id AS id,
             anyUser.username AS username,
             anyUser.slug AS slug,
             anyUser.photo AS photo,
             p.birthday AS birthday,
             p AS profile,
             collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options,
             collect(distinct {tag: tag, tagged: tagged}) AS tags,
             l AS location,
             matching_questions,
             similarity,
             like,
             hasCommonObjectives'
        )
            ->orderBy('hasCommonObjectives DESC', $orderQuery)
            ->skip('{ offset }')
            ->limit('{ limit }');

        $query = $qb->getQuery();
        $result = $query->getResultSet();
        $response = $this->buildResponseFromResult($result);
        $return['items'] = array_merge($return['items'], $response['items']);

        $needContent = $this->needMoreContent($limit, $return);
        if ($needContent) {

            $foreign = 0;
            if (isset($filtersArray['foreign'])) {
                $foreign = $filtersArray['foreign'];
            }
            $foreignResult = $this->getForeignContent($filtersArray, $needContent, $foreign);
            $return['items'] = array_merge($return['items'], $foreignResult['items']);
            $return['newForeign'] = $foreignResult['foreign'];
        }

        $needContent = $this->needMoreContent($limit, $return);
        if ($needContent) {
            $ignored = 0;
            if (isset($filtersArray['ignored'])) {
                $ignored = $filtersArray['ignored'];
            }

            $ignoredResult = $this->getIgnoredContent($filtersArray, $needContent, $ignored);
            $return['items'] = array_merge($return['items'], $ignoredResult['items']);
            $return['newIgnored'] = $ignoredResult['ignored'];
        }
        //Works with ContentPaginator (accepts $result), not Paginator (accepts $result['items'])
        return $return;
    }

    /**
     * Counts the total results from queryset.
     * @param array $filtersArray
     * @return int
     */
    public function countTotal(array $filtersArray)
    {
        $id = $filtersArray['id'];
        $count = 0;

        $filters = $this->applyFilters($filtersArray);

        $qb = $this->gm->createQueryBuilder();

        $parameters = array('userId' => (integer)$id);

        $qb->setParameters($parameters);

        $qb->match('(u:User {qnoow_id: {userId}}), (anyUser:UserEnabled)')
            ->where('u <> anyUser', 'NOT (u)-[:LIKES|:DISLIKES]->(anyUser)')
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
        $qb->where($filters['conditions'])
            ->with('u', 'anyUser', 'matching_questions', 'similarity', 'p', 'l');

        foreach ($filters['matches'] as $match) {
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