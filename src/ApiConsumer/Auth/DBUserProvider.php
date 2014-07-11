<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 28/06/14
 * Time: 18:34
 */

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

        $params = array(
            ':resource' => $resource
        );

        if (null !== $userId) {
            $sql .= " AND u.id = :userId";
            $params[':userId'] = $userId;
        }

        $sql .= ";";

        try {
            $users = $this->driver->fetchAll($sql, $params);
        } catch (\Exception $e) {
            throw $e;
        }

        return $users;
    }
}
