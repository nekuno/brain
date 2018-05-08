<?php

namespace Model\GhostUser;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\SocialNetwork\SocialProfile;
use Model\User\UserManager;
use Model\User\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GhostUserManager
{
    /** @var GraphManager */
    protected $graphManager;

    const LABEL_GHOST_USER = "GhostUser";

    /** @var UserManager */
    protected $userManager;

    function __construct(GraphManager $graphManager, UserManager $userManager)
    {
        $this->graphManager = $graphManager;
        $this->userManager = $userManager;
    }


    public function create()
    {

        $nextId = $this->userManager->getNextId();
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

    public function getById($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(u:' . $this::LABEL_GHOST_USER . ' {qnoow_id: { id }})')
            ->setParameter('id', (int)$id)
            ->returns('u, u.qnoow_id as id');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException(sprintf('User "%d" not found', $id));
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->buildOneGhostUser($row);
    }

    public function saveAsUser($id)
    {
        $ghostUser = $this->getById($id);

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(u:'.$this::LABEL_GHOST_USER.')')
            ->where('u.qnoow_id={id}')
            ->setParameter('id', (integer)$id)
            ->set('u.canReenable = true')
            ->remove('u:'.$this::LABEL_GHOST_USER)
            ->returns('u');

        $rs = $qb->getQuery()->getResultSet();
        $this->userManager->setEnabled($id, true);

        return $ghostUser;
    }

    public function saveAsGhost($id)
    {

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (integer)$id)
            ->set('u:' . $this::LABEL_GHOST_USER)
            ->returns('u, u.qnoow_id as id');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException(sprintf('User "%d" not found', $id));
        }

        /* @var $row Row */
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

    public function getBySocialProfile(SocialProfile $profile)
    {
        $user = $this->userManager->getBySocialProfile($profile);

        if ($user instanceof User) {
            try {
                return $this->getById($user->getId());
            } catch (NotFoundHttpException $e) {
                return null;
            }
        }

        return null;
    }

    public function getMostSimilarIds($userId, $userLimit)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User{qnoow_id:{userId}})')
            ->setParameter('userId', $userId);
        $qb->with('u')
            ->limit(1);

        $qb->match('(u)-[s:SIMILARITY]-(u2:GhostUser)')
            ->with('s.similarity AS similarity', 'u2.qnoow_id AS id')
            ->orderBy(' 1 - similarity ASC')// similarity DESC starts with NULL values
            ->limit('{limit}')
            ->setParameter('limit', $userLimit)
            ->returns('id');

        $result = $qb->getQuery()->getResultSet();

        return $this->userManager->buildIdsArray($result);
    }

    /**
     * @return array
     */
    public function getAllIds()
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(u:GhostUser)');
        $qb->returns('u.qnoow_id AS id');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->userManager->buildIdsArray($result);
    }
}