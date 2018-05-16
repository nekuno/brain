<?php

namespace Model\Shares;

use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Relationship;
use Model\Neo4j\GraphManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SharesManager
{
    /**
     * @var GraphManager
     */
    protected $graphManager;

    function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    public function get($userId1, $userId2)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u1:User{qnoow_id: {id1}})', '(u2:User{qnoow_id: {id2}})')
            ->setParameter('id1', (integer)$userId1)
            ->setParameter('id2', (integer)$userId2);

        $qb->match('(u1)-[shares:SHARES_WITH]-(u2)');

        $qb->returns('shares');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() === 0) {
            return null;
        }

        return $this->buildOne($result);
    }

    public function merge($userId1, $userId2, Shares $shares)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u1:User{qnoow_id: {id1}})', '(u2:User{qnoow_id: {id2}})')
            ->setParameter('id1', (integer)$userId1)
            ->setParameter('id2', (integer)$userId2);

        $qb->merge('(u1)-[shares:SHARES_WITH]-(u2)');

        foreach ($shares->toArray() as $parameter => $value) {
            $qb->set("shares.$parameter = { $parameter }")
                ->setParameter($parameter, $value);
        }

        $qb->returns('id(shares)');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() === 0) {
            $errorMessage = sprintf('Trying to share with nonexistant user %d or %d', $userId1, $userId2);
            throw new NotFoundHttpException($errorMessage);
        }

        $sharesId = $result->current()->offsetGet('shares');
        $shares->setId($sharesId);

        return $shares;
    }

    public function delete($userId1, $userId2)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u1:User{qnoow_id: {id1}})', '(u2:User{qnoow_id: {id2}})')
            ->setParameter('id1', (integer)$userId1)
            ->setParameter('id2', (integer)$userId2);

        $qb->match('(u1)-[shares:SHARES_WITH]-(u2)');
        $qb->delete('shares');

        $result = $qb->getQuery()->getResultSet();

        return $result->count();
    }

    protected function buildOne(ResultSet $resultSet)
    {
        /** @var Relationship $sharesRelationship */
        $sharesRelationship = $resultSet->current()->offsetGet('shares');

        $shares = new Shares();
        $shares->setId($sharesRelationship->getId());
        $shares->setTopLinks($sharesRelationship->getProperty('topLinks'));
        $shares->setSharedLinks($sharesRelationship->getProperty('sharedLinks'));

        return $shares;
    }
}