<?php

namespace Model\Recommendation;

use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;

class ProposalRecommendationPaginatedManager implements PaginatedInterface
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

    }



    /**
     * Counts the total results with filters.
     * @param array $filters
     * @return int
     */
    public function countTotal(array $filters)
    {
        return 3;
    }
}