<?php

namespace Model\User;

use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;

class UserLikedPaginatedManager implements PaginatedInterface
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
        return isset($filters['userId']);
    }

    public function slice(array $filters, $offset, $limit)
    {
        $userId = $filters['userId'];

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User)')
            ->where('u.qnoow_id = {id}')
            ->setParameter('id', $userId)
            ->with('u');

        $qb->optionalMatch('(u)-[:LIKES]->(otherUser:UserEnabled)')
            ->returns('otherUser.slug AS slug');

        $result = $qb->getQuery()->getResultSet();

        $userSlugs = array();
        /** @var Row $row */
        foreach ($result as $row) {
            $data = $qb->getData($row);
            if ($data['slug'] !== null){
                $userSlugs[] = $data['slug'];
            }
        }
        return $userSlugs;
    }

    public function countTotal(array $filters)
    {
        $userId = $filters['userId'];

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User)')
            ->where('u.qnoow_id = {id}')
            ->setParameter('id', $userId)
            ->with('u');

        $qb->optionalMatch('(u)-[:LIKES]->(otherUser:UserEnabled)')
            ->returns('count(otherUser) AS amount');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('amount');
    }
}