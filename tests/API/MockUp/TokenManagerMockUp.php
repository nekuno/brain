<?php

namespace Tests\API\MockUp;

use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Token\Token;
use Model\Token\TokenManager;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokenManagerMockUp extends TokenManager
{
    /**
     * @param int $userId
     * @param array $data
     * @return Token
     * @throws ValidationException|NotFoundHttpException|MethodNotAllowedHttpException
     */
    public function create($userId, array $data)
    {
        $this->validateOnCreate($data, $userId);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', (integer)$userId)
            ->merge('(user)<-[:TOKEN_OF]-(token:Token {createdTime: { createdTime }})')
            ->setParameter('createdTime', time())
            ->returns('user', 'token');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();
        $tokenNode = $row->offsetGet('token');

        $this->saveTokenData($tokenNode, $data);
        $token = $this->getByIdAndResourceOwner($userId, $data['resourceOwner']);

        return $token;
    }

    protected function refreshTokenData($token)
    {

    }
}