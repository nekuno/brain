<?php

namespace ApiConsumer\Auth;

use Doctrine\DBAL\Connection;

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
    public function updateAccessToken($resource, $userId, $accessToken, $creationTime, $expirationTime)
    {

        $sql = "UPDATE  user_access_tokens " .
            " SET oauthToken = :accessToken, " .
            " createdTime = :creationTime, " .
            " expireTime = :expirationTime " .
            " WHERE resourceOwner = :resource AND " .
            "user_id = :userId;";

        $params = array(
            ':accessToken' => $accessToken,
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

    /**
     * { @inheritdoc }
     */
    public function updateRefreshToken($refreshToken, $resource, $userId)
    {
        $sql = "UPDATE  user_access_tokens " .
            " SET refreshToken = :refreshToken " .
            " WHERE resourceOwner = :resource AND " .
            "user_id = :userId;";

        $params = array(
            ':refreshToken' => $refreshToken,
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
