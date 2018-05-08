<?php

namespace Service\Validator;

use Everyman\Neo4j\Query\ResultSet;
use Model\Neo4j\GraphManager;

class ExistenceValidator
{
    /** @var  GraphManager */
    protected $graphManager;

    /**
     * @param GraphManager $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    public function validateUserId($userId, $desired = true)
    {
        return $this->validateAttributeId('User', 'qnoow_id', $userId, $desired);
    }

    public function validateGroupId($groupId, $desired = true)
    {
        return $this->validateNodeId('Group', $groupId, $desired);
    }

    public function validateInvitationId($invitationId, $desired = true)
    {
        return $this->validateNodeId('Invitation', $invitationId, $desired);
    }

    public function validateQuestionId($questionId, $desired = true)
    {
        return $this->validateNodeId('Question', $questionId, $desired);
    }

    public function validateInvitationToken($token, $excludedId = null, $desired = true)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(invitation:Invitation)')
            ->where('toLower(invitation.token) = toLower({ token }) AND NOT id(invitation) = { excludedId }')
            ->setParameter('token', (string)$token)
            ->setParameter('excludedId', (int)$excludedId)
            ->returns('invitation');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $this->getExistenceErrors($result, $token, $desired);
    }

    public function validateAnswerId($questionId, $answerId, $desired = true)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(q:Question)<-[:IS_ANSWER_OF]-(a:Answer)')
            ->where('id(q) = { questionId }', 'id(a) = { answerId }')
            ->setParameter('questionId', (integer)$questionId)
            ->setParameter('answerId', (integer)$answerId)
            ->returns('a AS answer');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $this->getExistenceErrors($result, $answerId, $desired);
    }

    public function validateTokenResourceId($resourceId, $userId, $resourceOwner, $desired = true) {
        $conditions = array('token.resourceOwner = { resourceOwner }', 'token.resourceId = { resourceId }');
        if (null !== $userId) {
            $conditions[] = 'user.qnoow_id <> { id }';
        }
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(user:User)<-[:TOKEN_OF]-(token:Token)')
            ->where($conditions)
            ->setParameter('id', (integer)$userId)
            ->setParameter('resourceId', $resourceId)
            ->setParameter('resourceOwner', $resourceOwner)
            ->returns('user');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $this->getExistenceErrors($result, $resourceId, $desired);
    }

    public function validateRegistrationId($registrationId, $desired = true)
    {
        return $this->validateAttributeId('Device', 'registrationId', $registrationId, $desired);
    }

    protected function getExistenceErrors(ResultSet $result, $id, $desired)
    {
        $exists = $result->count() > 0;
        if ($desired && !$exists) {
            return array(sprintf('Node with id %s not found', $id));
        } else if (!$desired && $exists) {
            return array(sprintf('Node with id %s already exists', $id));
        }

        return array();
    }

    protected function validateAttributeId($label, $idName, $idValue, $desired)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match("(node:$label)")
            ->where("node.$idName = { idValue }")
            ->setParameter('idValue', $idValue)
            ->returns('node');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $this->getExistenceErrors($result, $idValue, $desired);
    }

    protected function validateNodeId($label, $id, $desired)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match("(node:$label)")
            ->where("id(node) = { idValue }")
            ->setParameter('idValue', $id)
            ->returns('node');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $this->getExistenceErrors($result, $id, $desired);
    }

}