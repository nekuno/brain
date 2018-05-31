<?php

namespace Tests\API\MockUp;

use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Token\Token;
use Model\Token\TokensManager;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokensManagerMockUp extends TokensManager
{
    /**
     * @param int $userId
     * @param string $resourceOwner
     * @param array $data
     * @return Token
     * @throws ValidationException|NotFoundHttpException|MethodNotAllowedHttpException
     */
    public function create($userId, $resourceOwner, array $data)
    {
        $data['resourceOwner'] = $resourceOwner;
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
        $token = $this->getById($userId, $resourceOwner);

        return $token;
    }

    protected function refreshTokenData($token)
    {

    }
}