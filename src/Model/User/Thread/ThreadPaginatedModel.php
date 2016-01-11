<?php

namespace Model\User\Thread;


use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThreadPaginatedModel implements PaginatedInterface
{
    /** @var ThreadManager */
    protected $threadManager;

    /** @var GraphManager */
    protected $graphManager;

    public function __construct(GraphManager $gm, ThreadManager $threadManager)
    {
        $this->graphManager = $gm;
        $this->threadManager = $threadManager;
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

        $qb->setParameters( array (
            'userId' => (integer) $filters['userId'],
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        ));

        $qb->match('(user:User)')
            ->where('user.qnoow_id= {userId}')
            ->optionalMatch('(user)-[:HAS_THREAD]->(thread:Thread)')
            ->returns('thread')
            ->skip('{offset}')
            ->limit('{limit}');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('User with id ' . $filters['userId'] . ' not found');
        }

        $threads = array();
        /** @var Row $row */
        foreach ($result as $row) {
            $threads[] = $this->threadManager->buildThread($row->offsetGet('thread'));
        }

        return $threads;
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
            ->optionalMatch('(user)-[:HAS_THREAD]->(thread:Thread)')
            ->returns('count(thread) as threads');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('threads');
    }
}