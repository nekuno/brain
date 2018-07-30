<?php

namespace Model\Proposal;

use Model\Neo4j\GraphManager;

class ProposalTagManager
{
    protected $graphManager;

    /**
     * ProposalTagManager constructor.
     * @param $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    public function deleteIfOrphan($name, $value)
    {
        $label = ucfirst($name);

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match("(tag:ProposalTag:$label)")
            ->where('tag.value = {value}')
            ->with('tag')
            ->setParameter('value', $value);

        $qb->optionalMatch('(tag)-[includes:INCLUDES]-()')
            ->with('tag', 'count(includes) AS amount');

        $qb->with('tag', 'amount')
            ->where('amount = 0');

        $qb->delete('tag');

        $qb->getQuery()->getResultSet();
    }
}