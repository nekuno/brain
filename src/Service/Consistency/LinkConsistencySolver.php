<?php

namespace Service\Consistency;

use Service\Consistency\ConsistencyErrors\ConsistencyError;
use Service\Consistency\ConsistencyErrors\RelationshipAmountConsistencyError;

class LinkConsistencySolver extends ConsistencySolver
{
    public function solve(ConsistencyError $error)
    {
        switch (get_class($error)){
            case RelationshipAmountConsistencyError::class:
                /** @var $error RelationshipAmountConsistencyError */
                return $this->solveRelationshipAmount($error);
            default:
                return parent::solve($error);
        }
    }

    protected function solveRelationshipAmount(RelationshipAmountConsistencyError $error)
    {
        $relationshipType = $error->getType();

        switch($relationshipType){
            case 'HAS_POPULARITY':
                $linkId = $error->getNodeId();
                $this->deleteExtraPopularities($linkId);
        }
    }
    //TODO: Move to popularityManager
    protected function deleteExtraPopularities($linkId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('id(l) = {linkId}')
            ->setParameter('linkId', $linkId)
            ->with('l');

        $qb->match('(l)-[:HAS_POPULARITY]->(p:Popularity)')
            ->returns('count(p) - 1 AS extra');

        $result = $qb->getQuery()->getResultSet();
        $extra = $result->current()->offsetGet('extra');

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('id(l) = {linkId}')
            ->setParameter('linkId', $linkId)
            ->with('l');

        $qb->match('(l)-[:HAS_POPULARITY]->(p:Popularity)')
            ->with('p')
            ->setParameter('extra', $extra)
            ->limit('{extra}');

        $qb->detachDelete('p');

        $qb->getQuery()->getResultSet();
    }

}