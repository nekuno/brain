<?php

namespace Model\Recommendation\Proposal;

use Model\Recommendation\AbstractUserRecommendator;

class CandidateRecommendator extends AbstractUserRecommendator
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
     * @throws \Exception
     */
    public function slice(array $filtersArray, $offset, $limit)
    {
        $limitInterested = ceil($limit*0.3);
        $limitUninterested = floor($limit*0.7);

        $proposalId = $filtersArray['proposalId'];
        $order = isset($filtersArray['userFilters']['order'])? $filtersArray['userFilters']['order'] : 'id DESC';
        $previousCondition = $this->buildPreviousCondition($filtersArray);

        $filters = $this->applyFilters($filtersArray);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(proposal:Proposal)')
            ->where('id(proposal) = {proposalId}')
            ->with('proposal')
            ->setParameter('proposalId', $proposalId);

        $qb->match('(user)-[:PROPOSES]->(proposal:Proposal)')
            ->with('proposal', 'user');

        $qb->optionalMatch('(proposal)<-[:INTERESTED_IN]-(anyUser:UserEnabled)-[:PROFILE_OF]-(p:Profile)')
            ->where($previousCondition)
            ->with('proposal', 'user', 'anyUser', 'p');

        $qb->optionalMatch('(p)-[:LOCATION]-(l:Location)')
            ->with('proposal', 'user', 'anyUser', 'p', 'l');

        foreach ($filters['matches'] as $match) {
            $qb->match($match);
        }
        $qb->where($filters['conditions']);

        $qb->with('proposal', 'user', 'anyUser')
            ->limit('{limitInterested}')
            ->setParameter('limitInterested', $limitInterested);

        $qb->with('proposal', 'user', 'collect(anyUser) AS interested');

        $previousCondition[] = 'NOT (proposal)--(:Availability)--(:DayPeriod)--(:Availability)--(anyUser)';
        $qb->optionalMatch('(anyUser:UserEnabled)')
            ->where($previousCondition)
            ->with('anyUser', 'proposal', 'user', 'interested');

        $qb->match('(anyUser)-[:PROFILE_OF]-(p)')
            ->with('anyUser', 'proposal', 'user', 'interested', 'p');

        $qb->optionalMatch('(p)-[:LOCATION]-(l:Location)')
            ->with('anyUser', 'proposal', 'user', 'interested', 'p', 'l');

        foreach ($filters['matches'] as $match) {
            $qb->match($match);
        }
        $qb->where($filters['conditions']);

        $qb->with('proposal', 'user', 'anyUser', 'interested')
            ->limit('{limitUninterested}')
            ->setParameter('limitUninterested', $limitUninterested);

        $qb->with('proposal', 'user', 'interested', 'collect(anyUser) AS uninterested')
            ->with('proposal', 'user', 'interested + uninterested AS candidates')
            ->unwind('candidates AS candidate')
            ->with('proposal', 'user', 'candidate');
        
        $qb->optionalMatch('(candidate)-[similarity:SIMILARITY]-(user)')
            ->with('candidate', 'proposal', 'user', 'similarity');
        $qb->optionalMatch('(candidate)-[matching:MATCHING]-(user)')
            ->with('candidate', 'proposal', 'user', 'similarity', 'matching');
        $qb->match('(candidate)-[:PROFILE_OF]-(p:Profile)')
            ->with('proposal', 'candidate', 'p', 'similarity', 'matching');

        $qb->optionalMatch('(p)-[:LOCATION]-(l:Location)')
            ->with('proposal', 'candidate', 'p', 'l', 'similarity', 'matching')
            ->optionalMatch('(p)<-[optionOf:OPTION_OF]-(option:ProfileOption)')
            ->with('proposal', 'candidate', 'p', 'l', 'similarity', 'matching',
                'collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options')
            ->optionalMatch('(p)-[tagged:TAGGED]-(tag:ProfileTag)')
            ->with('proposal', 'candidate', 'p', 'l', 'similarity', 'matching', 'options',
                'collect(distinct {tag: tag, tagged: tagged}) AS tags');

        $qb->where($filters['conditions']);

        $qb->returns(
            'candidate.qnoow_id AS id, 
            candidate.username AS username, 
            candidate.photo AS photo,
            candidate.createdAt AS createdAt,
            p.birthday AS birthday,
            p AS profile,
            l AS location,
            proposal, 
            similarity,
            options,
            tags'
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

        $qb->match('(anyUser:UserEnabled)')
            ->with('anyUser', 'proposal', 'user')
            ->where('NOT (proposal)-[:ACCEPTED|SKIPPED]->(anyUser)');

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

    /**
     * @param array $filtersArray
     * @return array
     */
    protected function buildPreviousCondition(array $filtersArray)
    {
        $includeSkipped = isset($filtersArray['includeSkipped']) ? $filtersArray['includeSkipped'] : false;

        $previousCondition = array('NOT (proposal)-[:ACCEPTED]->(anyUser)', 'NOT anyUser.qnoow_id = user.qnoow_id');
        if (!$includeSkipped){
            $previousCondition[] = 'NOT (proposal)-[:SKIPPED]->(anyUser)';
        }

        return $previousCondition;
    }
}