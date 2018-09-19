<?php

namespace Model\Recommendation\Proposal;

use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;

class ProposalRecommendator implements PaginatedInterface
{
    protected $graphManager;

    /**
     * ProposalRecommendationPaginatedManager constructor.
     * @param $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

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
        $userId = $filters['userId'];

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(user:User{qnoow_id:{userId}})')
            ->with('user')
            ->setParameter('userId', $userId);

        $qb->match('(user)-[:HAS_AVAILABILITY]->(:Availability)-[:INCLUDES]->(day:DayPeriod)');

        $qb->match('(day)<-[includes:INCLUDES]-(:Availability)<-[anyHas:HAS_AVAILABILITY]-(proposal:Proposal)')

            ->with('user', 'proposal')
            ->where('NOT ((user)-[:PROPOSES|SKIPPED]->(proposal))')
            ->with('proposal');

        $qb->returns('{id: id(proposal), text: proposal.text_es} AS proposal');

        $resultSet = $qb->getQuery()->getResultSet();

        $proposals = [];
        foreach ($resultSet as $row)
        {
            $data = $qb->getData($row);
            $proposals[] = $data['proposal'];
        }

        return $proposals;
    }

    /**
     * Counts the total results with filters.
     * @param array $filters
     * @return int
     */
    public function countTotal(array $filters)
    {
        $userId = $filters['userId'];

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(user:User{qnoow_id:{userId}})')
            ->with('user')
            ->setParameter('userId', $userId);

        $qb->match('(user)-[:HAS_AVAILABILITY]->(:Availability)-[:INCLUDES]->(day:Day)');

        $qb->match('(day)<-[:INCLUDES]-(:Availability)<-[:HAS_AVAILABILITY]-(proposal:Proposal)')
            ->where('NOT ((user)-[:PROPOSES]->(proposal))')
            ->with('proposal');

        $qb->returns('count(proposal) AS amount');

        $resultSet = $qb->getQuery()->getResultSet();

        $amount = $resultSet->current()->offsetGet('amount');

        return $amount;
    }
}