<?php

namespace Model\User;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\UserModel;
use Service\TokenGenerator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\Exception\RuntimeException;
use Symfony\Component\Security\Acl\Exception\Exception;

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


    public function __construct(GraphManager $gm, GroupModel $groupModel, UserModel $um)
    {

        $this->gm = $gm;
        $this->groupM = $groupModel;
        $this->um = $um;
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

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('id(inv) = { invitationId }')
            ->setParameter('invitationId', $id)
            ->returns('inv as invitation');

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
        $qb->match('(inv:Invitation)')
            ->where('inv.userId = { userId }')
            ->setParameter('userId', $userId)
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
        $qb = $this->gm->createQueryBuilder();
        $qb->match("(inv:Invitation)")
            ->returns('inv AS invitation')
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
        $qb = $this->gm->createQueryBuilder();
        $qb->match("(inv:Invitation)<-[:CREATED_INVITATION]-(u:User)")
            ->where("u.qnoow_id = { userId }")
            ->returns('inv AS invitation')
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

        $data += array('token' => null);
        $qb = $this->gm->createQueryBuilder();
        $qb->create('(inv:Invitation)')
            ->set('inv.consumed = 0', 'inv.createdAt = timestamp()');

        foreach($data as $index => $parameter) {
            if($index === 'userId')
                continue;
            if($index === 'token') {
                // set auto-created token if invitation has user or token is not set
                if(isset($data['userId']) || !$data['token']) {
                    $qb->set('inv.token = "' . $tokenGenerator->generateToken() . '"');
                    continue;
                }
            }
            $parameter = (string)$parameter === (string)(int)$parameter ? $parameter : "'" . $parameter . "'";
            $qb->set('inv.' . $index . ' = ' . $parameter );
        }

        if(isset($data['userId'])) {
             $qb->with('inv')
                 ->match('(user:User)')
                 ->where('user.qnoow_id = { userId }')
                 ->createUnique('(user)-[:CREATED_INVITATION]->(inv)')
                 ->setParameter('userId', $data['userId']);
        }
        $qb->returns('inv AS invitation');

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
            $parameter = (string)$parameter === (string)(int)$parameter ? $parameter : "'" . $parameter . "'";
            $qb->set('inv.' . $index . ' = ' . $parameter);
        }

        $qb->returns('inv AS invitation')
            ->setParameters(array(
                    'invitationId' => $data['invitationId'])
            );

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
            ->setParameter('invitationId', $invitationId)
            ->delete('inv', 'created', 'consumed');

        $query = $qb->getQuery();

        $query->getResultSet();
    }

    public function consume($invitationId, $userId)
    {
        if(!is_int($invitationId)) {
            throw new \RuntimeException('invitation ID must be an integer');
        }
        if(!is_int($userId)) {
            throw new \RuntimeException('user ID must be an integer');
        }
        if(!$this->existsInvitation($invitationId)) {
            throw new NotFoundHttpException(sprintf('There is not invitation with ID %s', $invitationId));
        }
        if($this->getAvailableInvitations($invitationId) < 1) {
            throw new NotFoundHttpException(sprintf('There are no more available usages for invitation with ID %s', $invitationId));
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)', '(u:User)')
            ->where('id(inv) = { invitationId } AND u.qnoow_id = { userId }')
            ->createUnique('(u)-[r:CONSUMED_INVITATION]->(inv)')
            ->set('inv.available = inv.available - 1', 'inv.consumed = inv.consumed + 1')
            ->returns('inv AS invitation')
            ->setParameters(array(
                'invitationId' => $invitationId,
                'userId' => $userId,
            ));

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function prepareSend($id, $userId, array $data)
    {
        if(!is_int($id)) {
            throw new \RuntimeException('invitation ID must be an integer');
        }
        if(!is_int($userId)) {
            throw new \RuntimeException('user ID must be an integer');
        }

        $user = $this->um->getById($data['userId']);
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
            'name' => $user['name'],
            'url' => '//nekuno.com/invitation/' . $invitation['token'],
            'expiresAt' => $invitation['expiresAt'],
        );
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
                            if(isset($data['userId'])) {
                                $fieldErrors[] = 'You cannot set the token';
                            }
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

        $optionalKeys = array('email', 'expiresAt', 'groupId', 'htmlText', 'slogan', 'image_url', 'orientationRequired');
        $requiredKeys = array('token', 'available', 'consumed', 'createdAt');
        $invitationArray = array();

        foreach ($requiredKeys as $key) {
            if (!$invitation->getProperty($key)) {
                throw new \RuntimeException(sprintf('%s key needed in row', $key));
            }
            $invitationArray[$key] = $invitation->getProperty($key);

        }
        foreach ($optionalKeys as $key) {
            $invitationArray[$key] = $invitation->getProperty($key);
        }

        $invitationArray += array('invitationId' => $invitation->getId());

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
            'email' => array(
                'required' => false,
            ),
            'expiresAt' => array(
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

        return $this->build($row);
    }
}