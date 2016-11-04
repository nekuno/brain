<?php

namespace Model\User\Group;

use Model\User\Recommendation\AbstractUserPaginatedModel;

class GroupMembersPaginatedModel extends AbstractUserPaginatedModel
{
    public function slice(array $filters, $offset, $limit)
    {
        $groupId = (int)$filters['groupId'];
        $userId = (int)$filters['userId'];

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group)')
            ->where('id(g) = {groupId}')
            ->with('g');
        $qb->setParameter('groupId', $groupId);

        $qb->match('(g)-[:BELONGS_TO]-(anyUser:User)')
            ->with('anyUser');
        $qb->match('(u2:User{qnoow_id:{userId}})')
            ->setParameter('userId', (int)$userId);
        $qb->optionalMatch('(anyUser)-[m:MATCHING]-(u2)')
            ->with('m.matching_questions as matching_questions, anyUser')
            ->orderBy('matching_questions DESC')
            ->skip('{offset}')
            ->limit('{limit}')
            ->setParameter('offset', (int)$offset)
            ->setParameter('limit', (int)$limit);

        $qb->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)')
            ->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->returns(
            'anyUser.qnoow_id AS id,
             anyUser.username AS username,
             anyUser.photo AS photo,
             p.birthday AS birthday,
             l.locality + ", " + l.country AS location',
            'matching_questions',
            '0 AS similarity',
            '0 AS like',
            '0 AS popularity'
        );

        $result = $qb->getQuery()->getResultSet();

        return $this->buildUserRecommendations($result);
    }

    public function countTotal(array $filters)
    {
        $groupId = (int)$filters['groupId'];

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group)')
            ->where('id(g) = {groupId}')
            ->with('g');
        $qb->setParameter('groupId', $groupId);

        $qb->match('(g)-[:BELONGS_TO]-(anyUser:User)')
            ->returns('count(anyUser) - 1 AS total');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('total');
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $hasGroupId = isset($filters['groupId']);
        $hasUserId = isset($filters['userId']);

        return $hasGroupId && $hasUserId;
    }

}