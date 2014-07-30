<?php

namespace ApiConsumer\Auth;

use Doctrine\DBAL\Connection;

class DBUserProvider implements UserProviderInterface
{

    /**
     * @var Connection
     */
    private $driver;

    public function __construct(Connection $driver)
    {

        $this->driver = $driver;
    }

    /**
     * { @inheritdoc }
     */
    public function getUsersByResource($resource, $userId = null)
    {

        $sql = "SELECT * " .
            " FROM users AS u" .
            " INNER JOIN user_access_tokens AS ut ON u.id = ut.user_id" .
            " WHERE ut.resourceOwner = :resource";

        $params[':resource'] = $resource;

        if (null !== $userId) {
            $sql .= " AND u.id = :userId";
            $params[':userId'] = $userId;
        }

        $sql .= ";";

        try {
            if ($userId) {
                return $this->driver->fetchAssoc($sql, $params);
            } else {
                return $this->driver->fetchAll($sql, $params);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * { @inheritdoc }
     */
    public function updateAccessToken($resource, $userId, $acessToken, $creationTime, $expirationTime )
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

            return $this->driver->executeUpdate($sql, $params);
            
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
