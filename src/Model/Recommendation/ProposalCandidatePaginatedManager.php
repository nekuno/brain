<?php

namespace Model\Recommendation;

use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;

class ProposalCandidatePaginatedManager implements PaginatedInterface
{
    protected $graphManager;

    protected $userRecommendationBuilder;

    /**
     * ProposalCandidatePaginatedManager constructor.
     * @param GraphManager $graphManager
     * @param UserRecommendationBuilder $userRecommendationBuilder
     */
    public function __construct(GraphManager $graphManager, UserRecommendationBuilder $userRecommendationBuilder)
    {
        $this->graphManager = $graphManager;
        $this->userRecommendationBuilder = $userRecommendationBuilder;
    }

    /**
     * Hook point for validating the $filters.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        return true;
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
        //mix
    }

    protected function getInterestedCandidates($filters, $offset, $limit)
    {
        $offset = floor($offset / 2);
        $limit = floor($limit / 2);

        $userId = $filters['userId'];

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(user:User{qnoow_id:{userId}})')
            ->with('user')
            ->setParameter('userId', $userId);

        $qb->match('(user)-[:PROFILE_OF]-(:Profile)-[:OPTION_OF]-(proposal:Proposal)')
            ->with('proposal');

        $qb->match('(proposal)<-[:INTERESTED_IN]-(anyUser:UserEnabled)-[:PROFILE_OF]-(profile:Profile)')
            ->with('collect(proposal) AS proposals', 'anyUser', 'profile');

        $qb->match('(profile)-[:LOCATION]-(location:Location)')
            ->match('(profile)-[:OPTION_OF]-(gender:DescriptiveGender)')
            ->returns(
                'anyUser.qnoow_id AS id, 
            anyUser.username AS username, 
            profile AS profile,
            location,
            gender,
            proposal'
            )
            ->skip('{offset}')
            ->limit('{limit}')
            ->setParameter('offset', $offset)
            ->setParameter('limit', $limit);

        $resultSet = $qb->getQuery()->getResultSet();

        $userRecommendations = $this->userRecommendationBuilder->buildUserRecommendations($resultSet);

        return $userRecommendations;
    }

    protected function getUnInterestedCandidates($filters, $offset, $limit)
    {
        $offset = ceil($offset / 2);
        $limit = floor($limit / 2);

        $userId = $filters['userId'];

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(user:User{qnoow_id:{userId}})')
            ->with('user')
            ->setParameter('userId', $userId);

        $qb->match('(user)-[:PROFILE_OF]-(:Profile)-[:OPTION_OF]-(proposal:Proposal)-[:HAS_AVAILABILITY]->(availability:Availability)')
            ->with('{id: id(proposal), text: proposal.text_es} AS proposal', 'availability');

        //TODO: Include filter by hour
        //TODO: Include filter by weekday
        $qb->match('(availability)-[:INCLUDES]->(:Day)<-[:INCLUDES]-(:Availability)<-[:HAS_AVAILABILITY]-(anyUser:UserEnabled)<-[:PROFILE_OF]-(profile:Profile)')
            ->with('proposal', 'anyUser');

        $qb->match('(profile)-[:LOCATION]-(location:Location)')
            ->match('(profile)-[:OPTION_OF]-(gender:DescriptiveGender)')
            ->returns(
                'anyUser.qnoow_id AS id, 
            anyUser.username AS username, 
            anyUser.slug AS slug,
            anyUser.photo AS photo,
            profile.birthday AS birthday,
            profile,
            location,
            collect(gender) AS gender,
            proposal'
            )
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

        $qb = $this->graphManager->createQueryBuilder();

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
}