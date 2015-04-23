<?php

namespace ApiConsumer\Auth;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class DBUserProvider implements UserProviderInterface
{

    /**
     * @var Connection
     */
    protected $connectionSocial;

    public function __construct(Connection $connectionSocial)
    {

        $this->connectionSocial = $connectionSocial;
    }

    /**
     * { @inheritdoc }
     */
    public function getUsersByResource($resource = null, $userId = null)
    {

        $sql = "SELECT * " .
            " FROM users AS u" .
            " INNER JOIN user_access_tokens AS ut ON u.id = ut.user_id" .
            " WHERE ";

        $filters = array();
        if (null !== $resource) {
            $filters[] = "ut.resourceOwner = :resource";
            $params[':resource'] = $resource;
        }

        if (null !== $userId) {
            $filters[] = "u.id = :userId";
            $params[':userId'] = (int)$userId;
        }

        $sql .= implode(" AND ", $filters);

        $sql .= ";";

        try {

            return $this->connectionSocial->fetchAll($sql, $params);
        } catch (DBALException $e) {
            throw $e;
        }
    }

    /**
     * { @inheritdoc }
     */
    public function updateAccessToken($resource, $userId, $acessToken, $creationTime, $expirationTime)
    {

        $sql = "UPDATE  user_access_tokens " .
            " SET oauthToken = :accessToken, " .
            " createdTime = :creationTime, " .
            " expireTime = :expirationTime " .
            " WHERE resourceOwner = :resource AND " .
            "user_id = :userId;";

        $params = array(
            ':accessToken' => $acessToken,
            ':creationTime' => $creationTime,
            ':expirationTime' => $expirationTime,
            ':resource' => $resource,
            ':userId' => $userId,
        );

        try {

            return $this->connectionSocial->executeUpdate($sql, $params);

        } catch (\Exception $e) {
            throw $e;
        }
    }
}
