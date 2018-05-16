<?php

namespace Model\Thread;

use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;

class ThreadPaginatedManager implements PaginatedInterface
{
    /**
     * @var GraphManager
     */
    protected $graphManager;

    public function __construct(GraphManager $gm)
    {
        $this->graphManager = $gm;
    }

    /**
     * Hook point for validating the $filters.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $idCorrect = isset($filters['userId']) && is_int($filters['userId']);

        return $idCorrect;
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

        $qb->setParameters(
            array(
                'userId' => (integer)$filters['userId'],
                'offset' => (integer)$offset,
                'limit' => (integer)$limit
            )
        );

        $qb->match('(user:User {qnoow_id: { userId }})')
            ->match('(user)-[:HAS_THREAD]->(thread:Thread)')
            ->returns('id(thread) AS id')
            ->orderBy('thread.updatedAt DESC')
            ->skip('{offset}')
            ->limit('{limit}');

        $result = $qb->getQuery()->getResultSet();

        $threadIds = array();
        foreach ($result as $row) {
            /* @var $row Row */
            $threadIds[] = $row->offsetGet('id');
        }

        return $threadIds;
    }

    /**
     * Counts the total results with filters.
     * @param array $filters
     * @return int
     */
    public function countTotal(array $filters)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->setParameter('id', (integer)$filters['userId']);

        $qb->match('(user:User)')
            ->where('user.qnoow_id= {id}')
            ->match('(user)-[:HAS_THREAD]->(thread:Thread)')
            ->returns('count(thread) as threads');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('threads');
    }
}