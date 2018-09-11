<?php

namespace Model\Recommendation;

class CandidateUninterestedFreePaginatedManager extends AbstractUserRecommendationPaginatedManager
{
    /**
     * Hook point for validating the $filters.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        return isset($filters['userId']);
    }

    /**
     * Slices the results according to $filters, $offset, and $limit.
     * @param array $filtersArray
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function slice(array $filtersArray, $offset, $limit)
    {
        $offset = ceil($offset / 2);
        $limit = ceil($limit / 2);

        $proposalId = $filtersArray['proposalId'];
        $order = isset($filtersArray['userFilters']['order'])? $filtersArray['userFilters']['order'] : 'id DESC';

        $filters = $this->applyFilters($filtersArray);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(proposal:Proposal)')
            ->where('id(proposal) = {proposalId}')
            ->with('proposal')
            ->setParameter('proposalId', $proposalId);

        $qb->match('(user)-[:PROPOSES]->(proposal:Proposal)')
            ->with('proposal', 'user');

        $qb->match('(anyUser:UserEnabled)')
            ->with('anyUser', 'proposal', 'user')
            ->where('NOT((proposal)<-[:INTERESTED_IN]-(anyUser))', 'NOT (proposal)-[:ACCEPTED|SKIPPED]->(anyUser)');
        //TODO: Include filter by weekday

        $qb->optionalMatch('(anyUser)-[similarity:SIMILARITY]-(user)')
            ->with('anyUser', 'proposal', 'user', 'similarity');
        $qb->optionalMatch('(anyUser)-[matching:MATCHING]-(user)')
            ->with('anyUser', 'proposal', 'user', 'similarity', 'matching');
        $qb->match('(anyUser:UserEnabled)-[:PROFILE_OF]-(p:Profile)')
            ->with('proposal', 'anyUser', 'p', 'similarity', 'matching');

        foreach ($filters['matches'] as $match) {
            $qb->match($match);
        }

        $qb->match('(p)-[:LOCATION]-(l:Location)')
            ->with('proposal, anyUser', 'p', 'l', 'similarity', 'matching')
            ->match('(p)-[:OPTION_OF]-(gender:DescriptiveGender)')
            ->with('proposal', 'anyUser', 'p', 'l', 'gender', 'similarity', 'matching');

        $qb->where($filters['conditions']);

        $qb->returns(
            'anyUser.qnoow_id AS id, 
            anyUser.username AS username, 
            anyUser.photo AS photo,
            anyUser.createdAt AS createdAt,
            p.birthday AS birthday,
            p AS profile,
            l AS location,
            collect(gender) AS gender,
            proposal, 
            similarity'
        )
            ->orderBy($order)
            ->skip('{offset}')
            ->limit('{limit}')
            ->setParameter('offset', $offset)
            ->setParameter('limit', $limit);

        $resultSet = $qb->getQuery()->getResultSet();

        $userRecommendations = $this->userRecommendationBuilder->buildUserRecommendations($resultSet);

        return $userRecommendations;
    }

    public function countTotal(array $filters)
    {
        $userId = $filters['userId'];

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User{qnoow_id:{userId}})')
            ->with('user')
            ->setParameter('userId', $userId);

        $qb->match('(user)-[:PROFILE_OF]-(:Profile)-[:OPTION_OF]-(proposal:Proposal)')
            ->with('proposal');

        $qb->where('NOT ((proposal)<-[:INTERESTED_IN]-(anyUser:UserEnabled))');

        $qb->returns('count(distinct anyUser) AS amount');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('amount');
    }


}