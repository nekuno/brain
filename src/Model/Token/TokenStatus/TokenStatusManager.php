<?php

namespace Model\Token\TokenStatus;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\Neo4j\QueryBuilder;
use Service\Validator\TokenStatusValidator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokenStatusManager
{
    /**
     * @var GraphManager
     */
    protected $graphManager;
    /**
     * @var TokenStatusValidator
     */
    protected $validator;

    /**
     * TokenStatusManager constructor.
     * @param $graphManager
     * @param $validator
     */
    public function __construct(GraphManager $graphManager, TokenStatusValidator $validator)
    {
        $this->graphManager = $graphManager;
        $this->validator = $validator;
    }

    /**
     * @param $userId
     * @param $resource
     * @param $fetched
     * @return TokenStatus
     */
    public function setFetched($userId, $resource, $fetched)
    {
        return $this->setBooleanParameter($userId, $resource, 'fetched', $fetched);
    }

    /**
     * @param $userId
     * @param $resource
     * @param $processed
     * @return TokenStatus
     */
    public function setProcessed($userId, $resource, $processed)
    {
        return $this->setBooleanParameter($userId, $resource, 'processed', $processed);
    }

    protected function setBooleanParameter($userId, $resource, $name, $value)
    {
        $this->validateOnCreate($value);

        $qb = $this->mergeTokenStatusQuery($userId, $resource);
        $this->setParameterQuery($qb, $name, (integer)$value);
        $this->setUpdateTimeQuery($qb);
        $this->returnStatusQuery($qb);

        $result = $qb->getQuery()->getResultSet();
        $tokenStatus = $this->buildOne($result);

        return $tokenStatus;
    }

    public function setUpdatedAt($userId, $resource, $updatedAt)
    {
        $qb = $this->mergeTokenStatusQuery($userId, $resource);
        $this->setParameterQuery($qb, 'updatedAt', (integer)$updatedAt);
        $this->returnStatusQuery($qb);

        $result = $qb->getQuery()->getResultSet();
        $tokenStatus = $this->buildOne($result);

        return $tokenStatus;
    }

    /**
     * @param $userId
     * @param null $resource
     * @return TokenStatus
     */
    public function getOne($userId, $resource)
    {
        $qb = $this->mergeTokenStatusQuery($userId, $resource);
        $this->returnStatusQuery($qb);

        $result = $qb->getQuery()->getResultSet();
        $tokenStatus = $this->buildOne($result);

        return $tokenStatus;
    }

    /**
     * @param $userId
     * @return TokenStatus[]
     */
    public function getAll($userId)
    {
        $qb = $this->mergeAllTokenStatusQuery($userId);
        $this->returnStatusQuery($qb);

        $result = $qb->getQuery()->getResultSet();
        $statuses = $this->buildMany($result);

        return $statuses;
    }

    /**
     * @param $userId
     * @param $resource
     * @return TokenStatus
     */
    public function removeOne($userId, $resource)
    {
        $tokenStatusToBeDeleted = $this->getOne($userId, $resource);

        $qb = $this->mergeTokenStatusQuery($userId, $resource);
        $this->deleteStatusQuery($qb);

        $qb->getQuery()->getResultSet();

        return $tokenStatusToBeDeleted;
    }

    public function removeAll($userId)
    {
        $qb = $this->mergeAllTokenStatusQuery($userId);
        $this->deleteStatusQuery($qb);

        $result = $qb->getQuery()->getResultSet();
        return $result->count();
    }

    protected function validateOnCreate($value)
    {
        $data = array('boolean' => $value);
        $this->validator->validateOnCreate($data);
    }

    /**
     * @param $userId
     * @param $resource
     * @return \Model\Neo4j\QueryBuilder
     */
    protected function mergeTokenStatusQuery($userId, $resource)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User{qnoow_id: {userId}})<-[:TOKEN_OF]-(token:Token{resourceOwner: {resource}})')
            ->with('token')
            ->limit(1);
        $qb->setParameters(
            array(
                'userId' => (integer)$userId,
                'resource' => $resource
            )
        );
        $qb->merge('(token)<-[:STATUS_OF]-(status:TokenStatus)')
            ->onCreate('SET status.updatedAt = timestamp()')
            ->with('status', 'token')
            ->limit(1);

        return $qb;
    }

    /**
     * @param $userId
     * @return \Model\Neo4j\QueryBuilder
     */
    protected function mergeAllTokenStatusQuery($userId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User{qnoow_id: {userId}})<-[:TOKEN_OF]-(token:Token)')
            ->with('token');
        $qb->setParameters(
            array(
                'userId' => (integer)$userId,
            )
        );

        $qb->merge('(token)<-[:STATUS_OF]-(status:TokenStatus)')
            ->onCreate('SET status.updatedAt = timestamp()')
            ->with('status', 'token');

        return $qb;
    }

    protected function setUpdateTimeQuery(QueryBuilder $qb)
    {
        $qb->set('status.updatedAt = timestamp()');
    }

    /**
     * @param QueryBuilder $qb
     * @param $name
     * @param $value
     * @internal param $fetched
     */
    protected function setParameterQuery(QueryBuilder $qb, $name, $value)
    {
        $qb->set("status.$name = {value}")
            ->setParameter('value', $value);
    }

    protected function deleteStatusQuery(QueryBuilder $qb)
    {
        $qb->detachDelete('(status)');
    }

    protected function returnStatusQuery(QueryBuilder $qb)
    {
        $qb->returns('status', 'token');
    }

    protected function buildOne(ResultSet $resultSet)
    {
        $this->checkResultCount($resultSet);

        $row = $resultSet->current();

        $this->checkResultStructure($row);

        return $this->buildOneFromRow($row);
    }

    protected function buildOneFromRow(Row $row)
    {
        /** @var Node $statusNode */
        $statusNode = $row->offsetGet('status');
        /** @var Node $tokenNode */
        $tokenNode = $row->offsetGet('token');

        $tokenStatus = new TokenStatus();

        $tokenStatus->setFetched((integer)$statusNode->getProperty('fetched'));
        $tokenStatus->setProcessed((integer)$statusNode->getProperty('processed'));
        $tokenStatus->setUpdatedAt($statusNode->getProperty('updatedAt'));
        $tokenStatus->setResourceOwner($tokenNode->getProperty('resourceOwner'));

        return $tokenStatus;
    }

    protected function buildMany(ResultSet $resultSet)
    {
        $statuses = array();
        foreach ($resultSet as $row) {
            $this->checkResultStructure($row);
            $statuses[] = $this->buildOneFromRow($row);
        }

        return $statuses;
    }

    protected function throwNotFoundException($message = 'Token not found')
    {
        throw new NotFoundHttpException($message);
    }

    /**
     * @param ResultSet $resultSet
     */
    protected function checkResultCount(ResultSet $resultSet)
    {
        if ($resultSet->count() != 1) {
            $this->throwNotFoundException('Result count expected to be 1, is ' . $resultSet->count());
        }
    }

    /**
     * @param $row
     */
    protected function checkResultStructure(Row $row)
    {
        if (!$row->offsetExists('status') || $row->offsetGet('status') == null) {
            $this->throwNotFoundException('Malformed result, expected status node to be returned');
        }
    }
}