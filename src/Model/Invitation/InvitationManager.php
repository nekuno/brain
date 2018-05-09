<?php

namespace Model\Invitation;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\Group\Group;
use Service\TokenGenerator;
use Service\Validator\InvitationValidator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class InvitationManager
{

    /**
     * @var TokenGenerator $tokenGenerator
     */
    protected $tokenGenerator;

    /**
     * @var GraphManager $gm
     */
    protected $gm;

    /**
     * @var InvitationValidator $validator
     */
    protected $validator;

    /**
     * @var string
     */
    protected $adminDomain;

    const MAX_AVAILABLE = 9999999999;

    public function __construct(TokenGenerator $tokenGenerator, GraphManager $gm, InvitationValidator $validator, $adminDomain)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->gm = $gm;
        $this->validator = $validator;
        $this->adminDomain = $adminDomain;
    }

    public function getCountTotal()
    {
        $count = 0;

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->returns('COUNT(DISTINCT inv) AS total');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            $count = $row->offsetGet('total');
        }

        return $count;
    }

    public function getById($id)
    {
        if (!is_numeric($id)) {
            throw new \RuntimeException('invitationId ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('id(inv) = { invitationId }')
            ->optionalMatch('(inv)-[:HAS_GROUP]->(g:Group)')
            ->optionalMatch('(u:User)-[:CREATED_INVITATION]->(inv)')
            ->setParameter('invitationId', (integer)$id)
            ->returns('inv as invitation', 'g AS group', 'u.qnoow_id AS userId');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();

            return $this->build($row);
        }

        throw new NotFoundHttpException(sprintf('There is not invitation with ID %s', $id));
    }

    public function getByGroupFollowersId($groupId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->setParameter('groupId', (integer)$groupId);
        $qb->match('(group:GroupFollowers)')
            ->where('id(group) = { groupId }')
            ->match('(group)<-[:HAS_GROUP]-(inv:Invitation)')
            ->returns('inv as invitation', 'group');
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            throw new NotFoundHttpException(sprintf('Group with id %s is not a GroupFollowers, doesn´t have invitation or doesn´t exist', $groupId));
        }

        $row = $result->current();

        return $this->build($row);
    }

    public function getCountByUserId($userId)
    {
        $count = 0;

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)<-[:CREATED_INVITATION]-(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->setParameter('userId', (integer)$userId)
            ->returns('COUNT(inv) as total');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            $count = $row->offsetGet('total');
        }

        return $count;
    }

    public function getPaginatedInvitations($offset, $limit)
    {
        if (!is_numeric($offset)) {
            throw new \RuntimeException('offset must be an integer');
        }
        if (!is_numeric($limit)) {
            throw new \RuntimeException('limit must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match("(inv:Invitation)")
            ->optionalMatch('(inv)-[:HAS_GROUP]->(g:Group)')
            ->optionalMatch('(u:User)-[:CREATED_INVITATION]->(inv)')
            ->returns('inv AS invitation', 'g AS group', 'u.qnoow_id AS userId')
            ->orderBy('inv.createdAt DESC')
            ->skip("{ offset }")
            ->limit("{ limit }")
            ->setParameters(
                array(
                    'offset' => (integer)$offset,
                    // TODO: Refactor when using invitations pagination
                    'limit' => 1000,
                )
            );

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $invitations = array();
        foreach ($result as $row) {
            $invitations[] = $this->build($row);
        }

        return $invitations;
    }

    public function getPaginatedInvitationsByUser($offset, $limit, $userId)
    {
        if (!is_numeric($offset)) {
            throw new \RuntimeException('offset must be an integer');
        }
        if (!is_numeric($limit)) {
            throw new \RuntimeException('limit must be an integer');
        }
        if (!is_numeric($userId)) {
            throw new \RuntimeException('$userId ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match("(inv:Invitation)<-[:CREATED_INVITATION]-(u:User)")
            ->where("u.qnoow_id = { userId }")
            ->optionalMatch('(inv)-[:HAS_GROUP]->(g:Group)')
            ->optionalMatch('(inv)<-[:CONSUMED_INVITATION]-(cu:User)')
            ->returns('inv AS invitation', 'g AS group', 'coalesce(cu.qnoow_id) as consumedUserId', 'coalesce(cu.usernameCanonical) as consumedUsername')
            ->skip("{ offset }")
            ->limit("{ limit }")
            ->setParameters(
                array(
                    'offset' => (integer)$offset,
                    // TODO: Refactor when using invitations pagination
                    'limit' => 1000,
                    'userId' => (integer)$userId,
                )
            );

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $invitations = array();
        foreach ($result as $row) {
            $invitations[] = $this->build($row);
        }

        return $invitations;
    }

    //TODO: Too many ifs, divide in methods (createFromUser, createFromAdmin, createFromGroup...)
    public function create(array $data)
    {
        $this->validateCreate($data);

        $userAvailable = 0;
        if (isset($data['userId'])) {
            $userAvailable = $this->getUserAvailable($data['userId']);
        }

        $data += array('token' => null);
        $qb = $this->gm->createQueryBuilder();
        $qb->create('(inv:Invitation)')
            ->set('inv.consumed = 0', 'inv.createdAt = timestamp()');

        foreach ($data as $index => $parameter) {
            if ($index === 'userId' || $index === 'createdAt' || $index === 'consumed' || $index === 'groupId') {
                continue;
            }
            if ($index === 'token') {
                // set auto-created token if invitation has user or token is not set
                if (isset($data['userId']) || !$data['token']) {
                    do {
                        $token = $this->tokenGenerator->generateToken();
                        $exists = $this->existsToken($token);
                    } while ($exists);
                    $qb->set('inv.token = "' . $token . '"');
                    continue;
                } else if ($data['token']) {
                    $qb->set('inv.token = toLower("' . $data['token'] . '")');
                    continue;
                }
            }
            if ($index === 'orientationRequired') {
                if (isset($data['orientationRequired'])) {
                    $data['orientationRequired'] = $data['orientationRequired'] ? 'true' : 'false';
                    $qb->set('inv.orientationRequired = ' . $data['orientationRequired']);
                    continue;
                }
            }
            if ($index === 'available') {
                if (isset($data['userId'])) {
                    $data['available'] = 1;
                }
                $qb->set('inv.available = ' . $data['available']);
                continue;
            }
            if (array_key_exists($index, $data)) {
                if (ctype_digit((string)$parameter)) {
                    $parameter = (integer)$parameter;
                } elseif (is_null($parameter)) {
                    $parameter = 'null';
                } else {
                    $parameter = "'" . $parameter . "'";
                }
                $qb->set('inv.' . $index . ' = ' . $parameter);
            }
        }

        if (isset($data['groupId'])) {
            $qb->with('inv')
                ->match('(g:Group)')
                ->where('id(g) = { groupId }')
                ->createUnique('(inv)-[:HAS_GROUP]->(g)');
            $qb->setParameter('groupId', (integer)$data['groupId']);
        }
        if (isset($data['userId'])) {
            isset($data['groupId']) ? $qb->with('inv', 'g') : $qb->with('inv');
            $qb->match('(user:User)')
                ->where('user.qnoow_id = { userId }')
                ->createUnique('(user)-[r:CREATED_INVITATION]->(inv)')
                ->set('user.available_invitations = { userAvailable } - 1');

            if (isset($data['groupId'])) {
                $qb->with('user', 'inv', 'g')
                    ->optionalMatch('(gf:GroupFollowers)')
                    ->with('user', 'inv', 'g', 'collect(gf) as gfs')
                    ->for_each('( g in gfs | SET user.available_invitations = { userAvailable } )');
            }

            $qb->setParameter('userId', (integer)$data['userId']);
            $qb->setParameter('userAvailable', (integer)$userAvailable);

        }

        if (isset($data['groupId'])) {
            $qb->returns('inv AS invitation', 'g AS group');
        } else {
            $qb->returns('inv AS invitation');
        }

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function update(array $data)
    {
        $this->validateUpdate($data);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('id(inv) = { invitationId }');

        foreach ($data as $index => $parameter) {
            if ($index === 'userId' || $index === 'createdAt' || $index === 'consumed' || $index === 'groupId') {
                continue;
            }
            if ($index === 'orientationRequired') {
                if (isset($data['orientationRequired'])) {
                    $data['orientationRequired'] = $data['orientationRequired'] ? 'true' : 'false';
                    $qb->set('inv.orientationRequired = ' . $data['orientationRequired']);
                    continue;
                }
            }
            if (array_key_exists($index, $data)) {
                if (ctype_digit((string)$parameter)) {
                    $parameter = (integer)$parameter;
                } elseif (is_null($parameter)) {
                    $parameter = 'null';
                } else {
                    $parameter = "'" . $parameter . "'";
                }
                $qb->set('inv.' . $index . ' = ' . $parameter);
            }
        }
        $qb->setParameters(
            array(
                'invitationId' => (integer)$data['invitationId']
            )
        );

        if (array_key_exists('groupId', $data)) {
            if (isset($data['groupId'])) {
                $qb->with('inv')
                    ->optionalMatch('(inv)-[ohg:HAS_GROUP]->(og:Group)')
                    ->delete('ohg')
                    ->with('inv')
                    ->match('(g:Group)')
                    ->where('id(g) = { groupId }')
                    ->createUnique('(inv)-[hg:HAS_GROUP]->(g)')
                    ->setParameters(
                        array(
                            'groupId' => (integer)$data['groupId'],
                            'invitationId' => (integer)$data['invitationId'],
                        )
                    );
            } else {
                $qb->with('inv')
                    ->optionalMatch('(inv)-[hg:HAS_GROUP]->(g:Group)')
                    ->delete('hg')
                    ->setParameters(
                        array(
                            'invitationId' => (integer)$data['invitationId'],
                        )
                    );
            }
        }
        if (array_key_exists('userId', $data)) {
            isset($data['groupId']) ? $qb->with('inv, g') : $qb->with('inv');
            $qb->optionalMatch('(old_user:User)-[our:CREATED_INVITATION]->(inv)')
                ->delete('our');
            if (isset($data['userId'])) {
                isset($data['groupId']) ? $qb->with('inv, g') : $qb->with('inv');
                $qb->match('(user:User)')
                    ->where('user.qnoow_id = { userId }')
                    ->createUnique('(user)-[r:CREATED_INVITATION]->(inv)')
                    ->setParameters(
                        array(
                            'userId' => (integer)$data['userId'],
                            'groupId' => isset($data['groupId']) ? (integer)$data['groupId'] : null,
                            'invitationId' => (integer)$data['invitationId'],
                        )
                    );
            }
        }

        if (isset($data['groupId'])) {
            $qb->returns('inv AS invitation', 'g AS group');
        } else {
            $qb->returns('inv AS invitation');
        }

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function remove($invitationId)
    {
        if (!is_numeric($invitationId)) {
            throw new \RuntimeException('invitation ID must be an integer');
        }
        if (!$this->existsInvitation($invitationId)) {
            throw new NotFoundHttpException(sprintf('There is not invitation with ID %s', $invitationId));
        }
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(inv:Invitation)')
            ->where('id(inv) = { invitationId }')
            ->optionalMatch('(:User)-[created:CREATED_INVITATION]->(inv)')
            ->optionalMatch('(:User)-[consumed:CONSUMED_INVITATION]->(inv)')
            ->optionalMatch('(inv)-[has_group:HAS_GROUP]->(:Group)')
            ->setParameter('invitationId', (integer)$invitationId)
            ->delete('inv', 'created', 'consumed', 'has_group');

        $query = $qb->getQuery();

        $query->getResultSet();
    }

    public function removeAll()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(inv:Invitation)')
            ->optionalMatch('(:User)-[created:CREATED_INVITATION]->(inv)')
            ->optionalMatch('(:User)-[consumed:CONSUMED_INVITATION]->(inv)')
            ->optionalMatch('(inv)-[has_group:HAS_GROUP]->(:Group)')
            ->delete('inv', 'created', 'consumed');

        $query = $qb->getQuery();

        $query->getResultSet();
    }

    public function consume($token, $userId)
    {
        if (!is_numeric($token) && !is_string($token)) {
            throw new \RuntimeException('token must be numeric or string');
        }
        if (!is_numeric($userId)) {
            throw new \RuntimeException('user ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)', '(u:User)')
            ->where('toLower(inv.token) = toLower({ token }) AND coalesce(inv.available, 0) > 0 AND u.qnoow_id = { userId }')
            ->optionalMatch('(inv)-[:HAS_GROUP]->(g:Group)')
            ->createUnique('(u)-[r:CONSUMED_INVITATION]->(inv)')
            ->set('inv.available = inv.available - 1', 'inv.consumed = inv.consumed + 1')
            ->returns('inv AS invitation, g as group')
            ->setParameters(
                array(
                    'token' => (string)$token,
                    'userId' => (integer)$userId,
                )
            );

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();

            return $this->build($row);
        }

        throw new NotFoundHttpException(sprintf('There is not invitation available with token %s', $token));
    }

    public function prepareSend($id, $userName, array $data, $socialHost)
    {
        if (!is_numeric($id)) {
            throw new \RuntimeException('invitation ID must be an integer');
        }

        $invitation = $this->getById($id);

        /* TODO should we get the stored email? */
        if (!isset($data['email'])) {
            throw new \RuntimeException('email must be set');
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('email is not valid');
        }

        return array(
            'email' => $data['email'],
            'username' => $userName,
            'url' => $socialHost . 'invitation/' . (string)$invitation['invitation']['token'],
            'expiresAt' => (integer)$invitation['invitation']['expiresAt'],
        );
    }

    /* Not used but may needed to initialize available invitations */
    public function setUserAvailable($userId, $nOfAvailable)
    {
        if (!is_numeric($nOfAvailable)) {
            throw new \RuntimeException('nOfAvailable must be an integer');
        }
        if (!is_numeric($userId)) {
            throw new \RuntimeException('userId ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->set('u.available_invitations = { nOfAvailable }')
            ->setParameters(
                array(
                    'nOfAvailable' => (integer)$nOfAvailable,
                    'userId' => (integer)$userId,
                )
            );

        $query = $qb->getQuery();

        $query->getResultSet();
    }

    public function getUserAvailable($userId)
    {
        if (!is_numeric($userId)) {
            throw new \RuntimeException('userId ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->returns('u.available_invitations AS available_invitations')
            ->setParameters(
                array(
                    'userId' => (integer)$userId,
                )
            );

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $row->offsetGet('available_invitations');
    }

    public function setAvailableInvitations($token, $available)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters(
            array(
                'token' => $token,
                'available' => (integer)$available,
            )
        );
        $qb->match('(inv:Invitation)')
            ->where('inv.token = { token }')
            ->set('inv.available = { available }')
            ->with('inv')
            ->optionalMatch('(inv)-[:HAS_GROUP]->(g:Group)')
            ->returns('inv AS invitation', 'g AS group');
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();

            return $this->build($row);
        }

        throw new NotFoundHttpException(sprintf('Invitation with token %s not found', $token));
    }

    /**
     * @param $token
     * @param $excludedId
     * @return bool
     * @throws \Model\Neo4j\Neo4jException
     */
    public function existsToken($token, $excludedId = null)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(invitation:Invitation)')
            ->where('toLower(invitation.token) = toLower({ token }) AND NOT id(invitation) = { excludedId }')
            ->setParameter('token', (string)$token)
            ->setParameter('excludedId', (int)$excludedId)
            ->returns('invitation');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $result->count() > 0;
    }

    /**
     * @param $token
     * @param $id
     * @return string
     * @throws \Model\Neo4j\Neo4jException
     */
    protected function isTokenFromInvitationId($token, $id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(invitation:Invitation)')
            ->where('id(invitation) = { id }')
            ->setParameter('id', (integer)$id)
            ->returns('invitation.token AS token');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return strtolower($result->current()->offsetGet('token')) === strtolower($token);
    }

    public function validateTokenAvailable($token)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('toLower(inv.token) = toLower({ token }) AND coalesce(inv.available, 0) > 0')
            ->optionalMatch('(inv)-[:HAS_GROUP]->(g:Group)')
            ->returns('inv AS invitation', 'g AS group')
            ->setParameters(
                array(
                    'token' => (string)$token,
                )
            );

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() === 0 && substr($token, 0, 12) === "shared_user-") {
            $result = $this->createFromSharedUser($token);
        }
        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();

            return $this->build($row);
        }

        throw new ValidationException(null, sprintf('There is no invitation available with token %s', $token));
    }

    public function validateUpdate(array $data)
    {
        $this->validator->validateOnUpdate($data);
    }

    /**
     * @param array $data
     * @throws ValidationException
     */
    public function validateCreate(array $data)
    {
        $this->validator->validateOnCreate($data);
    }


    protected function build(Row $row)
    {
        return array(
            'invitation' => $this->buildInvitation($row),
        );
    }

    protected function buildInvitation(Row $row)
    {
        /** @var Node $invitation */
        $invitation = $row->offsetGet('invitation');
        /** @var Node $groupNode */
        $groupNode = $row->offsetExists('group') ? $row->offsetGet('group') : null;

        $userId = $row->offsetExists('userId') ? $row->offsetGet('userId') : null;

        $consumedUserId = $row->offsetExists('consumedUserId') ? $row->offsetGet('consumedUserId') : null;
        $consumedUsername = $row->offsetExists('consumedUsername') ? $row->offsetGet('consumedUsername') : null;

        $optionalKeys = array('email', 'expiresAt', 'htmlText', 'slogan', 'image_url', 'image_path', 'orientationRequired');
        $requiredKeys = array('token', 'available', 'consumed', 'createdAt');
        $invitationArray = array();

        foreach ($requiredKeys as $key) {
            if (null === $invitation->getProperty($key)) {
                throw new \RuntimeException(sprintf('%s key needed in row', $key));
            }
            $invitationArray[$key] = $invitation->getProperty($key);

        }
        foreach ($optionalKeys as $key) {
            $invitationArray[$key] = $invitation->getProperty($key);
        }

        $invitationArray += array('invitationId' => $invitation->getId());

        if ($groupNode) {
            $invitationArray['group'] = Group::createFromNode($groupNode);
        }

        if ($userId) {
            $invitationArray += array('userId' => $userId);
        }

        if (!$groupNode && $consumedUserId && $consumedUsername) {
            $invitationArray += array('consumedUserId' => $consumedUserId, 'consumedUsername' => $consumedUsername);
        }

        if (isset($invitationArray['image_path'])) {
            $invitationArray['image_url'] = $this->adminDomain . $invitationArray['image_path'];
        }

        return $invitationArray;
    }

    /**
     * @param $invitationId
     * @return bool
     * @throws \Exception
     */
    protected function existsInvitation($invitationId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('id(inv) = { invitationId }')
            ->setParameter('invitationId', (integer)$invitationId)
            ->returns('inv AS Invitation');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $result->count() > 0;
    }

    protected function getAvailableInvitations($invitationId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('id(inv) = { invitationId }')
            ->setParameter('invitationId', (integer)$invitationId)
            ->returns('inv.available AS available');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return (integer)$row->offsetGet('available');
    }

    private function createFromSharedUser($token)
    {
        $otherUserId = substr($token, 12);
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { otherUserId }})')
            ->merge('(u)-[:CREATED_INVITATION]-(inv:Invitation:InvitationSharedUser {token: { token }})')
            ->set('inv.available = COALESCE(inv.available, 10000) - 1', 'inv.consumed = COALESCE(inv.consumed, 0) + 1', 'inv.createdAt = COALESCE(inv.createdAt , timestamp())', 'inv.orientationRequired = true')
            ->returns('inv AS invitation')
            ->setParameters(
                array(
                    'otherUserId' => (integer)$otherUserId,
                    'token' => (string)$token,
                )
            );
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $result;
    }
}