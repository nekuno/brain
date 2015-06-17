<?php

namespace Model\User;

use Event\AnswerEvent;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    public function __construct(GraphManager $gm, GroupModel $groupModel, UserModel $um, EventDispatcher $eventDispatcher)
    {

        $this->gm = $gm;
        $this->groupM = $groupModel;
        $this->um = $um;
        $this->eventDispatcher = $eventDispatcher;
    }

/*    public function countTotal(array $filters)
    {

        $count = 0;

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->setParameter('userId', (integer)$filters['id'])
            ->match('(u)-[r:RATES]->(q:Question)')
            ->returns('COUNT(DISTINCT r) AS total');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            $row = $result->current();
            /* @var $row Row */
/*            $count = $row->offsetGet('total');
        }

        return $count;
    }
*/
    public function getById($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('id(inv) = { invitationId }')
            ->setParameter('invitationId', $id)
            ->returns('inv as invitation');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);

    }

    public function getCountByUserId($userId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('inv.userId = { userId }')
            ->setParameter('userId', $userId)
            ->returns('COUNT(inv) as totalInvitations');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $row->offsetGet('totalInvitations');

    }

    public function create(array $data)
    {

        $this->validate($data, false);

        $qb = $this->gm->createQueryBuilder();
        $qb->createUnique('(inv:Invitation)')
            ->set('inv.consumed = 0', 'inv.createdAt = timestamp()');

        foreach($data as $index => $parameter)
            $qb->set('inv.' . $index . ' = ' . $parameter);

        if(isset($data['userId']))
        {
             $qb
                ->with('inv')
                ->createUnique('(user:User)-[:CREATED_INVITATION]->(inv)')
                ->where('user.qnoow_id = { userId }')
                ->returns('inv AS invitation')
                ->setParameters($data);
        }

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        $this->handleAnswerAddedEvent($data);

        return $this->build($row);
    }

    public function consume($invitationId, $userId)
    {

        if(!$this->existsInvitation($invitationId)) {
            throw new NotFoundHttpException(sprintf('There is not invitation with ID "%s"', $invitationId));
        }
        if($this->getAvailableInvitations($invitationId) < 1) {
            throw new NotFoundHttpException(sprintf('There are no more available usages for invitation with ID "%s"', $invitationId));
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

    public function update(array $data)
    {

        $this->validate($data, false, true);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(inv:Invitation)')
            ->where('id(inv) = { invitationId }');

        foreach($data as $index => $parameter)
            $qb->set('inv.' . $index . ' = ' . $parameter);

        $qb->returns('inv AS invitation')
           ->setParameters(array(
                'invitationId' => $data['invitationId'])
            );


        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        $this->handleAnswerAddedEvent($data);

        return $this->build($row);
    }

    /**
     * @param $userId
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function getNumberOfUserAnswers($userId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(a:Answer)<-[ua:ANSWERS]-(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->setParameter('userId', (integer)$userId)
            ->returns('count(ua) AS nOfAnswers');

        $query = $qb->getQuery();

        return $query->getResultSet();
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

            if ($userRequired && $fieldName === 'userRequired') {
                $fieldMetadata['required'] = true;
            }
            if ($invitationIdRequired && $fieldName === 'invitationId') {
                $fieldMetadata['required'] = true;
            }

            $fieldErrors = array();

            if ($fieldMetadata['required'] === true && !isset($data[$fieldName])) {

                $fieldErrors[] = sprintf('The field "%s" is required', $fieldName);

            } else {

                $fieldValue = isset($data[$fieldName]) ? $data[$fieldName] : null;

                switch ($fieldName) {
                    case 'invitationId':
                        if (!is_int($fieldValue)) {
                            $fieldErrors[] = 'invitationId must be an integer';
                        } elseif (!$this->existsInvitation($fieldValue)) {
                            $fieldErrors[] = 'Invalid invitation ID';
                        }
                        break;
                    case 'token':
                        if (!is_string($fieldValue) && !is_numeric($fieldValue)) {
                            $fieldErrors[] = 'token must be a string or a numeric';
                        }
                        break;
                    case 'available':
                        if (!is_int($fieldValue)) {
                            $fieldErrors[] = 'available must be an integer';
                        }
                        break;
                    case 'email':
                        if (!filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
                            $fieldErrors[] = 'email must be a valid email';
                        }
                        break;
                    case 'expiresAt':
                        if (!(string)(int)$fieldErrors === (string)$fieldErrors) {
                            $fieldErrors[] = 'expiresAt must be a valid timestamp';
                        }
                        break;
                    case 'groupId':
                        if (!is_int($fieldValue)) {
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
                        if (!is_bool($fieldErrors)) {
                            $fieldErrors[] = 'orientationRequired must be a boolean';
                        }
                        break;
                    case 'userId':
                        if ($fieldValue) {
                            if (!is_int($fieldValue)) {
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

    public function build(Row $row)
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
        $properties = $invitation->getProperties();
        foreach ($requiredKeys as $key) {
            if (!in_array($key, $properties)) {
                throw new \RuntimeException(sprintf('"%s" key needed in row', $key));
            }
            $invitationArray[$key] = $invitation->getProperty($key);
        }
        foreach ($optionalKeys as $key) {
            if (in_array($key, $properties)) {
                $invitationArray[$key] = $invitation->getProperty($key);
            } else {
                $invitationArray[$key] = null;
            }
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
                'required' => true,
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
            'createdAt' => array(
                'required' => true,
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

    protected function handleAnswerAddedEvent(array $data)
    {
        $event = new AnswerEvent($data['userId'], $data['questionId']);
        $this->eventDispatcher->dispatch(\AppEvents::ANSWER_ADDED, $event);
    }
}