<?php

/**
 * @author Manolo Salsas (manolez@gmail.com)
 */

namespace Model\User;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\UserModel;
use Service\TokenGenerator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class InvitationModel
 * @package Model\User
 */
class InvitationModel
{
    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var GroupModel
     */
    protected $groupM;

    /**
     * @var UserModel
     */
    protected $um;

    /**
     * @var string
     */
    protected $adminDomain;

    public function __construct(GraphManager $gm, GroupModel $groupModel, UserModel $um, $adminDomain)
    {
        $this->gm = $gm;
        $this->groupM = $groupModel;
        $this->um = $um;
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
        if((string)$id !== (string)(int)$id) {
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
        if((string)$offset !== (string)(int)$offset) {
            throw new \RuntimeException('offset must be an integer');
        }
        if((string)$limit !== (string)(int)$limit) {
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
                    'limit' => (integer)$limit,
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
        if((string)$offset !== (string)(int)$offset) {
            throw new \RuntimeException('offset must be an integer');
        }
        if((string)$limit !== (string)(int)$limit) {
            throw new \RuntimeException('limit must be an integer');
        }
        if((string)$userId !== (string)(int)$userId) {
            throw new \RuntimeException('$userId ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match("(inv:Invitation)<-[:CREATED_INVITATION]-(u:User)")
            ->where("u.qnoow_id = { userId }")
            ->optionalMatch('(inv)-[:HAS_GROUP]->(g:Group)')
            ->returns('inv AS invitation', 'g AS group')
            ->skip("{ offset }")
            ->limit("{ limit }")
            ->setParameters(
                array(
                    'offset' => (integer)$offset,
                    'limit' => (integer)$limit,
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

    public function create(array $data, TokenGenerator $tokenGenerator)
    {
        $this->validate($data, false);

        $userAvailable = 0;
        if(isset($data['userId']) && !$userAvailable = $this->getUserAvailable($data['userId'])) {
            throw new \RuntimeException(sprintf('User %s has not available invitations', $data['userId']));
        }

        $data += array('token' => null);
        $qb = $this->gm->createQueryBuilder();
        $qb->create('(inv:Invitation)')
            ->set('inv.consumed = 0', 'inv.createdAt = timestamp()');

        foreach($data as $index => $parameter) {
            if($index === 'userId' || $index === 'createdAt' || $index === 'consumed' || $index === 'groupId')
                continue;
            if($index === 'token') {
                // set auto-created token if invitation has user or token is not set
                if(isset($data['userId']) || !$data['token']) {
                    $qb->set('inv.token = "' . $tokenGenerator->generateToken() . '"');
                    continue;
                }
            }
            if($index === 'orientationRequired') {
                if(isset($data['orientationRequired'])) {
                    $data['orientationRequired'] = $data['orientationRequired'] ? 'true' : 'false';
                    $qb->set('inv.orientationRequired = ' . $data['orientationRequired']);
                    continue;
                }
            }
            if(array_key_exists($index, $data)) {
                if(ctype_digit((string)$parameter)) {
                    $parameter = (integer)$parameter;
                } elseif(is_null($parameter)) {
                    $parameter = 'null';
                } else {
                    $parameter = "'" . $parameter . "'";
                }
                $qb->set('inv.' . $index . ' = ' . $parameter );
            }
        }

        if(isset($data['groupId'])) {
            $qb->with('inv')
                ->match('(g:Group)')
                ->where('id(g) = { groupId }')
                ->createUnique('(inv)-[:HAS_GROUP]->(g)')
                ->setParameters(array(
                    'groupId' => (integer)$data['groupId'],
                ));
        }
        if(isset($data['userId'])) {
            isset($data['groupId']) ? $qb->with('inv', 'g') : $qb->with('inv');
            $qb->match('(user:User)')
                 ->where('user.qnoow_id = { userId }')
                 ->createUnique('(user)-[r:CREATED_INVITATION]->(inv)')
                 ->set('user.available_invitations = { userAvailable } - 1')
                 ->setParameters(array(
                     'userId' => (integer)$data['userId'],
                     'userAvailable' => (integer)$userAvailable,
                     'groupId' => (integer)$data['groupId'],
                 ));
        }
        if(isset($data['groupId'])) {
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
        $this->validate($data, false, true);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('id(inv) = { invitationId }');

        foreach($data as $index => $parameter) {
            if($index === 'userId' || $index === 'createdAt' || $index === 'consumed' || $index === 'groupId')
                continue;
            if($index === 'orientationRequired') {
                if(isset($data['orientationRequired'])) {
                    $data['orientationRequired'] = $data['orientationRequired'] ? 'true' : 'false';
                    $qb->set('inv.orientationRequired = ' . $data['orientationRequired']);
                    continue;
                }
            }
            if(array_key_exists($index, $data)) {
                if(ctype_digit((string)$parameter)) {
                    $parameter = (integer)$parameter;
                } elseif(is_null($parameter)) {
                    $parameter = 'null';
                } else {
                    $parameter = "'" . $parameter . "'";
                }
                $qb->set('inv.' . $index . ' = ' . $parameter );
            }
        }
        $qb->setParameters(array(
                'invitationId' => (integer)$data['invitationId'])
        );

        if(array_key_exists('groupId', $data)) {
            if(isset($data['groupId'])) {
                $qb->with('inv')
                    ->optionalMatch('(inv)-[ohg:HAS_GROUP]->(og:Group)')
                    ->delete('ohg')
                    ->with('inv')
                    ->match('(g:Group)')
                    ->where('id(g) = { groupId }')
                    ->createUnique('(inv)-[hg:HAS_GROUP]->(g)')
                    ->setParameters(array(
                        'groupId' => (integer)$data['groupId'],
                        'invitationId' => (integer)$data['invitationId'],
                    ));
            } else {
                $qb->with('inv')
                    ->optionalMatch('(inv)-[hg:HAS_GROUP]->(g:Group)')
                    ->delete('hg')
                    ->setParameters(array(
                        'invitationId' => (integer)$data['invitationId'],
                    ));
            }
        }
        if(isset($data['userId'])) {
            isset($data['groupId']) ? $qb->with('inv, g') : $qb->with('inv');
            $qb->optionalMatch('(old_user:User)-[our:CREATED_INVITATION]->(inv)')
                ->delete('our');
            isset($data['groupId']) ? $qb->with('inv, g') : $qb->with('inv');
            $qb->match('(user:User)')
                ->where('user.qnoow_id = { userId }')
                ->createUnique('(user)-[r:CREATED_INVITATION]->(inv)')
                ->setParameters(array(
                    'userId' => (integer)$data['userId'],
                    'groupId' => isset($data['groupId']) ? (integer)$data['groupId'] : null,
                    'invitationId' => (integer)$data['invitationId'],
                ));

        }

        if(isset($data['groupId'])) {
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
        if(!is_int($invitationId)) {
            throw new \RuntimeException('invitation ID must be an integer');
        }
        if(!$this->existsInvitation($invitationId)) {
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
        if(!is_numeric($token) && !is_string($token)) {
            throw new \RuntimeException('token must be numeric or string');
        }
        if((string)$userId !== (string)(int)$userId) {
            throw new \RuntimeException('user ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)', '(u:User)')
            ->where('inv.token = { token } AND coalesce(inv.available, 0) > 0 AND u.qnoow_id = { userId }')
            ->createUnique('(u)-[r:CONSUMED_INVITATION]->(inv)')
            ->set('inv.available = inv.available - 1', 'inv.consumed = inv.consumed + 1')
            ->returns('inv AS invitation')
            ->setParameters(array(
                'token' => (string)$token,
                'userId' => (integer)$userId,
            ));

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            return $this->build($row);
        }

        throw new NotFoundHttpException(sprintf('There is not invitation available with token %s', $token));
    }

    public function prepareSend($id, $userId, array $data, $socialHost)
    {
        if((string)$id !== (string)(int)$id) {
            throw new \RuntimeException('invitation ID must be an integer');
        }
        if((string)$userId !== (string)(int)$userId) {
            throw new \RuntimeException('user ID must be an integer');
        }

        $user = $this->um->getById($userId);
        $invitation = $this->getById($id);

        /* TODO should we get the stored email? */
        if(!isset($data['email'])) {
            throw new \RuntimeException('email must be set');
        }
        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('email is not valid');
        }

        return array(
            'email' => $data['email'],
            'username' => $user['username'],
            'url' => $socialHost . 'invitation/' . (string)$invitation['invitation']['token'],
            'expiresAt' => (integer)$invitation['invitation']['expiresAt'],
        );
    }

    /* Not used but may needed to initialize available invitations */
    public function setUserAvailable($userId, $nOfAvailable)
    {
        if((string)$nOfAvailable !== (string)(int)$nOfAvailable) {
            throw new \RuntimeException('nOfAvailable must be an integer');
        }
        if((string)$userId !== (string)(int)$userId) {
            throw new \RuntimeException('userId ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->set('u.available_invitations = { nOfAvailable }')
            ->setParameters(array(
                'nOfAvailable' => (integer)$nOfAvailable,
                'userId' => (integer)$userId,
            ));

        $query = $qb->getQuery();

        $query->getResultSet();
    }

    /* Already user in subscriber
    public function addUserAvailable($userId, $nOfAvailable)
    {
        if((string)$nOfAvailable !== (string)(int)$nOfAvailable) {
            throw new \RuntimeException('nOfAvailable must be an integer');
        }
        if((string)$userId !== (string)(int)$userId) {
            throw new \RuntimeException('userId ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->set('u.available_invitations = u.available_invitations + { nOfAvailable }')
            ->setParameters(array(
                'nOfAvailable' => (integer)$nOfAvailable,
                'userId' => (integer)$userId,
            ));

        $query = $qb->getQuery();

        $query->getResultSet();
    }*/

    public function getUserAvailable($userId)
    {
        if((string)$userId !== (string)(int)$userId) {
            throw new \RuntimeException('userId ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->returns('u.available_invitations AS available_invitations')
            ->setParameters(array(
                'userId' => (integer)$userId,
            ));

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $row->offsetGet('available_invitations');
    }

    public function validateToken($token)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('inv.token = { token } AND coalesce(inv.available, 0) > 0')
            ->optionalMatch('(inv)-[:HAS_GROUP]->(g:Group)')
            ->returns('inv AS invitation', 'g AS group')
            ->setParameters(array(
                'token' => (string)$token,
            ));

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            return $this->build($row);
        }

        throw new ValidationException(sprintf('There is not invitation available with token %s', $token));
    }

    /**
     * @param array $data
     * @param bool $userRequired
     * @param bool $invitationIdRequired
     * @throws ValidationException
     */
    public function validate(array $data, $userRequired = true, $invitationIdRequired = false)
    {
        $errors = array();

        foreach ($this->getFieldsMetadata() as $fieldName => $fieldMetadata) {

            if ($userRequired && $fieldName === 'userId') {
                $fieldMetadata['required'] = true;
            }
            if ($invitationIdRequired && $fieldName === 'invitationId') {
                $fieldMetadata['required'] = true;
            }

            $fieldErrors = array();

            if ($fieldMetadata['required'] === true && !isset($data[$fieldName])) {

                $fieldErrors[] = sprintf('The field %s is required', $fieldName);

            } else {

                $fieldValue = isset($data[$fieldName]) ? $data[$fieldName] : null;

                if(null !== $fieldValue)
                {
                    switch ($fieldName) {
                        case 'invitationId':
                            if ((string)(int)$fieldValue !== (string)$fieldValue) {
                                $fieldErrors[] = 'invitationId must be an integer';
                            } elseif (!$this->existsInvitation($fieldValue)) {
                                $fieldErrors[] = 'Invalid invitation ID';
                            }
                            break;
                        case 'token':
                            /* Admin can create/update invitation with userId
                            if(isset($data['userId'])) {
                                $fieldErrors[] = 'You cannot set the token';
                            }*/
                            if (!is_string($fieldValue) && !is_numeric($fieldValue)) {
                                $fieldErrors[] = 'token must be a string or a numeric';
                            }
                            break;
                        case 'available':
                            if ((string)(int)$fieldValue !== (string)$fieldValue) {
                                $fieldErrors[] = 'available must be an integer';
                            }
                            break;
                        case 'email':
                            if (!filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
                                $fieldErrors[] = 'email must be a valid email';
                            }
                            break;
                        case 'expiresAt':
                            if ((string)(int)$fieldValue !== (string)$fieldValue) {
                                $fieldErrors[] = 'expiresAt must be a valid timestamp';
                            }
                            break;
                        case 'groupId':
                            if ((string)(int)$fieldValue !== (string)$fieldValue) {
                                $fieldErrors[] = 'groupId must be an integer';
                            } elseif (!$this->groupM->existsGroup($fieldValue)) {
                                $fieldErrors[] = 'Invalid group ID';
                            }
                            break;
                        case 'htmlText':
                            if (!is_string($fieldValue)) {
                                $fieldErrors[] = 'htmlText must be a string';
                            }
                            break;
                        case 'slogan':
                            if (!is_string($fieldValue)) {
                                $fieldErrors[] = 'slogan must be a string';
                            }
                            break;
                        case 'image_url':
                            if (!filter_var($fieldValue, FILTER_VALIDATE_URL)) {
                                $fieldErrors[] = 'image_url must be a valid URL';
                            }
                            break;
                        case 'image_path':
                            if (!preg_match('/^[\w\/\\-]+\.(png|jpe?g|gif|tiff)$/i', $fieldValue)) {
                                $fieldErrors[] = 'image_path must be a valid path';
                            }
                            break;
                        case 'orientationRequired':
                            if (!is_bool($fieldValue)) {
                                $fieldErrors[] = 'orientationRequired must be a boolean';
                            }
                            break;
                        case 'userId':
                            if ($fieldValue) {
                                if ((string)(int)$fieldValue !== (string)$fieldValue) {
                                    $fieldErrors[] = 'userId must be an integer';
                                } else {
                                    try {
                                        $this->um->getById($fieldValue);
                                    } catch (NotFoundHttpException $e) {
                                        $fieldErrors[] = $e->getMessage();
                                    }
                                }
                            }
                            break;
                        default:
                            $fieldErrors[] = $fieldName . ' cannot be set';
                            break;
                    }
                }
            }

            if (count($fieldErrors) > 0) {
                $errors[$fieldName] = $fieldErrors;
            }

        }

        if (count($errors) > 0) {
            $e = new ValidationException('Validation error');
            $e->setErrors($errors);
            throw $e;
        }
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
        /** @var Node $group */
        $group = $row->offsetExists('group') ? $row->offsetGet('group') : null;

        $userId = $row->offsetExists('userId') ? $row->offsetGet('userId') : null;

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

        if($group) {
            $invitationArray += array('group' => array(
                'id' => $group->getId(),
                'name' => $group->getProperty('name'),
                'html' => $group->getProperty('html'),
            ));
        }

        if($userId) {
            $invitationArray += array('userId' => $userId);
        }

        if(isset($invitationArray['image_path'])) {
            $invitationArray['image_url'] = $this->adminDomain . $invitationArray['image_path'];
        }

        return $invitationArray;
    }

    /**
     * @return array
     */
    protected function getFieldsMetadata()
    {
        $metadata = array(
            'invitationId' => array(
                'required' => false,
            ),
            'token' => array(
                'required' => false,
            ),
            'available' => array(
                'required' => true,
            ),
            'consumed' => array(
                'required' => false,
            ),
            'email' => array(
                'required' => false,
            ),
            'expiresAt' => array(
                'required' => false,
            ),
            'createdAt' => array(
                'required' => false,
            ),
            'userId' => array(
                'required' => false,
            ),
            'groupId' => array(
                'required' => false,
            ),
            'htmlText' => array(
                'required' => false,
            ),
            'slogan' => array(
                'required' => false,
            ),
            'image_url' => array(
                'required' => false,
            ),
            'image_path' => array(
                'required' => false,
            ),
            'orientationRequired' => array(
                'required' => false,
            ),
        );

        return $metadata;
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
}