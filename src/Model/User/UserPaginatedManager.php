<?php

namespace Model\User;

use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;

class UserPaginatedManager implements PaginatedInterface
{
    protected $graphManager;

    protected $userManager;

    /**
     * UserPaginatedModel constructor.
     * @param $graphManager
     * @param $userManager
     */
    public function __construct(GraphManager $graphManager, UserManager $userManager)
    {
        $this->graphManager = $graphManager;
        $this->userManager = $userManager;
    }

    public function validateFilters(array $filters)
    {
        return true;
    }

    public function slice(array $filters, $offset, $limit)
    {
        $orderQuery = $this->getOrderQuery($filters);
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User)')
            ->where('NOT u:GhostUser');

        $qb->returns('u')
            ->orderBy($orderQuery)
            ->skip('{offset}')
            ->setParameter('offset', (integer)$offset)
            ->limit('{limit}')
            ->setParameter('limit', (integer)$limit);

        $result = $qb->getQuery()->getResultSet();

        $users = array();
        /** @var Row $row */
        foreach ($result as $row) {
            $users[] = $this->userManager->build($row);
        }

        return $users;
    }

    public function countTotal(array $filters)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User)')
            ->where('NOT u:GhostUser');

        $qb->returns('count(u) AS usersCount');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('usersCount');
    }

    protected function getOrderQuery($filters)
    {
        $defaultOrder = 'u.qnoow_id';
        $defaultOrderDir = 'desc';

        if (!isset($filters['order']) || !isset($filters['orderDir'])) {
            return $defaultOrder . ' ' . $defaultOrderDir;
        }

        switch ($filters['order']) {
            case 'id':
                $order = 'u.qnoow_id';
                break;
            case 'name':
                $order = 'u.username';
                break;
            default:
                $order = $defaultOrder;
        }

        $orderDir = $filters['orderDir'] ?: $defaultOrderDir;

        return $order . ' ' . $orderDir;
    }
}