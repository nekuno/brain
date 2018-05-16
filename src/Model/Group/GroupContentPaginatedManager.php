<?php

namespace Model\Group;

use Model\Recommendation\AbstractContentPaginatedManager;

class GroupContentPaginatedManager extends AbstractContentPaginatedManager
{
    public function slice(array $filters, $offset, $limit)
    {
        $groupId = (int)$filters['groupId'];
        $this->validator->validateGroupId($groupId);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group)')
            ->where('id(g) = {groupId}')
            ->with('g');
        $qb->setParameter('groupId', $groupId);

        $qb->match('(g)-[:BELONGS_TO]-(:User)-[r:LIKES]-(l:Link)')
            ->returns('l', 'count(r) AS likes')
            ->orderBy('likes DESC')
            ->skip('{offset}')
            ->limit('{limit}')
            ->setParameter('offset', (int)$offset)
            ->setParameter('limit', (int)$limit);

        $result = $qb->getQuery()->getResultSet();

        $response = $this->buildResponseFromResult($result);

        return $response['items'];
    }

    public function countTotal(array $filters)
    {
        $groupId = (int)$filters['groupId'];

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group)')
            ->where('id(g) = {groupId}')
            ->with('g');
        $qb->setParameter('groupId', $groupId);

        $qb->match('(g)-[:BELONGS_TO]-(:User)-[r:LIKES]-(l:Link)')
            ->returns('count(l) as total');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('total');
    }

}