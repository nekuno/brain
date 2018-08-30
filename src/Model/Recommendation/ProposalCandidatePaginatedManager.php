<?php

namespace Model\Recommendation;

class ProposalCandidatePaginatedManager extends AbstractUserRecommendationPaginatedManager
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
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function slice(array $filters, $offset, $limit)
    {
        $interestedCandidates = $this->getInterestedCandidates($filters, $offset, $limit);
        $uninterestedCandidates = $this->getUninterestedCandidates($filters, $offset, $limit);

        $candidates = $this->mixCandidates($interestedCandidates, $uninterestedCandidates);

        return $candidates;
    }

    protected function getInterestedCandidates($filtersArray, $offset, $limit)
    {
        $offset = floor($offset / 2);
        $limit = floor($limit / 2);

        $userId = $filtersArray['userId'];
        $order = isset($filtersArray['userFilters']['order'])? $filtersArray['userFilters']['order'] : 'id DESC';

        $filters = $this->applyFilters($filtersArray);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User{qnoow_id:{userId}})')
            ->with('user')
            ->setParameter('userId', $userId);

        $qb->match('(user)-[:PROPOSES]->(proposal:Proposal)')
            ->with('proposal', 'user');

        $qb->match('(proposal)<-[:INTERESTED_IN]-(anyUser:UserEnabled)-[:PROFILE_OF]-(p:Profile)')
            ->with('proposal', 'anyUser', 'user', 'p')
            ->where('NOT (proposal)-[:ACCEPTED]->(anyUser)');

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

    protected function getUnInterestedCandidates($filtersArray, $offset, $limit)
    {
        $offset = ceil($offset / 2);
        $limit = ceil($limit / 2);

        $userId = $filtersArray['userId'];
        $order = isset($filtersArray['userFilters']['order'])? $filtersArray['userFilters']['order'] : 'id DESC';

        $filters = $this->applyFilters($filtersArray);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User{qnoow_id:{userId}})')
            ->with('user')
            ->setParameter('userId', $userId);

        $qb->match('(user)-[:PROPOSES]->(proposal:Proposal)')
            ->with('proposal', 'user');

        $qb->match('(proposal)-[:HAS_AVAILABILITY]->(availability:Availability)')
            ->with('availability', 'proposal', 'user');

        $qb->match('(availability)-[:INCLUDES]-(:Day)-[includes:INCLUDES]-(:Availability)-[anyHas:HAS_AVAILABILITY]-(anyUser:User)')
            //range A "fits" range B if A.min is inside B, or if A.max is inside B
            //->where((includes fits anyHas) OR (anyHas fits includes))
            ->where('((includes.min > anyHas.min AND includes.min < anyHas.max) OR (includes.max < anyHas.max AND includes.max > anyHas.min)) 
                    OR ((anyHas.min > includes.min AND anyHas.min < includes.max) OR (anyHas.max < includes.max AND anyHas.max > includes.min))')
            ->with('anyUser', 'proposal', 'user')
            ->where('NOT((proposal)<-[:INTERESTED_IN]-(anyUser))', 'NOT (proposal)-[:ACCEPTED]->(anyUser)');
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

        $qb->match('(proposal)<-[:INTERESTED_IN]-(anyUser:UserEnabled)');

        $qb->returns('count(distinct anyUser) AS amount');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('amount');
    }

    protected function mixCandidates($interested, $unIntenterested)
    {
        $length = count($interested);

        $candidates = array();
        for ($i = 0; $i < $length; $i++) {
            $partial = [$interested[$i], $unIntenterested[$i]];
            shuffle($partial);

            $candidates = array_merge($candidates, $partial);
        }

        if (count($unIntenterested) > $length) {
            $extra = array_slice($unIntenterested, $length);
            $candidates = array_merge($candidates, $extra);
        }

        return $candidates;
    }
}