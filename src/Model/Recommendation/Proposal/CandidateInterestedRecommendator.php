<?php

namespace Model\Recommendation\Proposal;

use Model\Recommendation\AbstractUserRecommendator;

class CandidateInterestedRecommendator extends AbstractUserRecommendator
{
    /**
     * Hook point for validating the $filters.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        return isset($filters['proposalId']);
    }

    /**
     * Slices the results according to $filters, $offset, and $limit.
     * @param array $filtersArray
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function slice(array $filtersArray, $offset, $limit)
    {
        $offset = floor($offset / 2);
        $limit = floor($limit / 2);

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

        $qb->match('(proposal)<-[:INTERESTED_IN]-(anyUser:UserEnabled)-[:PROFILE_OF]-(p:Profile)')
            ->with('proposal', 'anyUser', 'user', 'p')
            ->where('NOT (proposal)-[:ACCEPTED|SKIPPED]->(anyUser)');

        $qb->optionalMatch('(anyUser)-[similarity:SIMILARITY]-(user)')
            ->with('anyUser', 'proposal', 'user', 'p', 'similarity');
        $qb->optionalMatch('(anyUser)-[matching:MATCHING]-(user)')
            ->with('anyUser', 'proposal', 'p', 'similarity', 'matching');

        foreach ($filters['matches'] as $match) {
            $qb->match($match);
        }
        $qb->with('proposal', 'anyUser', 'p', 'similarity', 'matching');

        $qb->match('(p)-[:LOCATION]-(l:Location)')
            ->match('(p)-[:OPTION_OF]-(gender:DescriptiveGender)');

        $qb->where($filters['conditions']);

        $qb->returns(
            'anyUser.qnoow_id AS id, 
            anyUser.username AS username, 
            p AS profile,
            l AS location,
            collect(gender) AS gender,
            proposal',
            'similarity'
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

    public function countTotal(array $filtersArray)
    {
        $proposalId = $filtersArray['proposalId'];
        $filters = $this->applyFilters($filtersArray);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(proposal:Proposal)')
            ->where('id(proposal) = {proposalId}')
            ->with('proposal')
            ->setParameter('proposalId', $proposalId);

        $qb->match('(user)-[:PROPOSES]->(proposal:Proposal)')
            ->with('proposal', 'user');

        $qb->match('(proposal)<-[:INTERESTED_IN]-(anyUser:UserEnabled)');

        $qb->match('(anyUser)-[:PROFILE_OF]-(p:Profile)');
        $qb->match('(p)-[:LOCATION]-(l:Location)');
        $qb->with('anyUser', 'proposal', 'user', 'p', 'l');

        foreach ($filters['matches'] as $match) {
            $qb->match($match);
        }

        $qb->with('anyUser', 'proposal', 'user');

        $qb->where($filters['conditions']);

        $qb->returns('count(distinct anyUser) AS amount');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('amount');
    }
}