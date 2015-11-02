<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 30/10/15
 * Time: 12:36
 */

namespace Model\User\GhostUser;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\UserModel;

class GhostUserManager
{
    /** @var GraphManager */
    protected $graphManager;

    const LABEL_GHOST_USER = "GhostUser";

    /** @var UserModel */
    protected $userModel;

    function __construct(GraphManager $graphManager, UserModel $userModel)
    {
        $this->graphManager = $graphManager;
        $this->userModel = $userModel;
    }


    public function create()
    {

        $nextId = $this->userModel->getNextId();
        $qb = $this->graphManager->createQueryBuilder();
        $qb->create('(u:User:' . $this::LABEL_GHOST_USER . ')')
            ->set('u.createdAt = { createdAt }', 'u.qnoow_id = {qnoow_id}')
            ->setParameters(array(
                'createdAt' => (new \DateTime())->format('Y-m-d H:i:s'),
                'qnoow_id' => $nextId,
            ))
            ->returns('u, u.qnoow_id as id');
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            //TODO: Throw "couldnt create" exception
            return null;
        }

        $row = $result->current();
        return $this->buildOneGhostUser($row);

    }

    /**
     * @param ResultSet $result
     * @return array of GhostUser
     */
    protected function buildGhostUsers(ResultSet $result)
    {
        $ghostUsers = array();
        /** @var Row $row */
        foreach ($result as $row) {
            $ghostUsers[] = $this->buildOneGhostUser($row);
        }

        return $ghostUsers;
    }

    protected function buildOneGhostUser(Row $row)
    {
        $id = $row->offsetGet('id');
        /** @var Node $node */
        $node = $row->offsetGet('u');

        $ghostUser = new GhostUser($id);

        $ghostUser->setCreatedAt($node->getProperty('createdAt'));

        return $ghostUser;
    }
}