<?php

namespace Model\Popularity;


use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;

class PopularityPaginatedModel implements PaginatedInterface
{
    protected $graphManager;
    protected $popularityManager;

    /**
     * PopularityPaginatedModel constructor.
     * @param GraphManager $graphManager
     * @param PopularityManager $popularityManager
     */
    public function __construct(GraphManager $graphManager, PopularityManager $popularityManager)
    {
        $this->graphManager = $graphManager;
        $this->popularityManager = $popularityManager;
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
        $qb = $this->graphManager->createQueryBuilder();

        $qb->setParameters(array(
            'offset' => (integer) $offset,
            'limit' => (integer) $limit,
        ));

        $qb->match('(popularity:Popularity)')
            ->with('popularity')
            ->orderBy('id(popularity) DESC')
            ->skip('{offset}')
            ->limit('{limit}');
        $qb->returns('   id(popularity) AS id',
            'popularity.popularity AS popularity',
            'popularity.unpopularity AS unpopularity',
            'popularity.timestamp AS timestamp',
            'true AS new');
        $result = $qb->getQuery()->getResultSet();
        
        $popularities = $this->popularityManager->build($result);
        
        return $popularities;
    }

    /**
     * Counts the total results with filters.
     * @param array $filters
     * @return int
     */
    public function countTotal(array $filters)
    {
        return 1000000;
    }
}