<?php

namespace Model\Proposal;

use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;

class OwnProposalLikedPaginated implements PaginatedInterface
{
    protected $graphManager;

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
     * ProposalRecommendationPaginatedManager constructor.
     * @param $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    public function slice(array $filters, $offset, $limit)
    {
        $proposals = $this->getMatchedProposals($filters, $offset, $limit);

        $unmatchedLimit = count($proposals) < $limit;
        if ($unmatchedLimit > 0) {
            $unmatchedProposals = $this->getUnmatchedProposals($filters, $offset, $unmatchedLimit);
            $proposals = array_merge($proposals, $unmatchedProposals);
        }

        return $proposals;
    }

    protected function getMatchedProposals($filters, $offset, $limit)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User{qnoow_id: {userId}})')
            ->with('u')
            ->setParameter('userId', $filters['userId']);

        $qb->match('(u)-[:INTERESTED_IN]->(proposal:Proposal)')
            ->where('(u)<-[:ACCEPTED]-(proposal)')
            ->with('u', 'proposal')
            ->skip($offset)
            ->limit($limit);

        $qb->with('id(proposal) AS proposalId', 'true AS hasMatch')
            ->returns('{proposalId: proposalId, hasMatch: hasMatch} AS proposalData');

        $result = $qb->getQuery()->getResultSet();

        $items = array();
        foreach ($result as $row) {
            $items[] = $qb->getData($row);
        }

        return $items;
    }

    protected function getUnmatchedProposals($filters, $offset, $limit)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User{qnoow_id: {userId}})')
            ->with('u')
            ->setParameter('userId', $filters['userId']);

        $qb->match('(u)-[:INTERESTED_IN]->(proposal:Proposal)')
            ->where('NOT (u)<-[:ACCEPTED]-(proposal)')
            ->with('u', 'proposal')
            ->skip($offset)
            ->limit($limit);

        $qb->with('id(proposal) AS proposalId', 'false AS hasMatch')
            ->returns('{proposalId: proposalId, hasMatch: hasMatch} AS proposalData');

        $result = $qb->getQuery()->getResultSet();

        $items = array();

        foreach ($result as $row) {
            $items[] = $qb->getData($row);
        }
        return $items;
    }

    /**
     * Counts the total results with filters.
     * @param array $filters
     * @return int
     */
    public function countTotal(array $filters)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User{qnoow_id: {userId}})')
            ->with('u')
            ->setParameter('userId', $filters['userId']);

        $qb->optionalMatch('(u)-[:INTERESTED_IN]->(proposal:Proposal)')
            ->returns('count(proposal) AS amount');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('amount');
    }
}