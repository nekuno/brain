<?php

namespace Model\Recommendation;

class UserPopularRecommendationPaginatedManager extends AbstractUserRecommendationPaginatedManager
{

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
        return array('items' => $this->getUsersByPopularity($filters, $offset, $limit));
    }

    /**
     * Counts the total results from queryset.
     * @param array $filtersArray
     * @throws \Exception
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

        $qb->match('(u:User {qnoow_id: {userId}})-[:MATCHES|SIMILARITY]-(anyUser:User)')
            ->where('u <> anyUser')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with(
                'u, anyUser,
            (CASE WHEN EXISTS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions,
            (CASE WHEN EXISTS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity'
            )
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('u, anyUser, matching_questions, similarity, p, l');
        $qb->where(
            array_merge(
                array('(matching_questions > 0 OR similarity > 0)'),
                $filters['conditions']
            )
        )
            ->with('u', 'anyUser', 'matching_questions', 'similarity', 'p', 'l');

        foreach ($filters['matches'] as $match) {
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
}