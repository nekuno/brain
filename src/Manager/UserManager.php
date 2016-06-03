<?php

namespace Manager;

use Event\UserEvent;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\Neo4j\Neo4jException;
use Model\User;
use Model\User\GhostUser\GhostUserManager;
use Model\User\LookUpModel;
use Model\User\SocialNetwork\SocialProfile;
use Model\User\TokensModel;
use Model\User\UserComparedStatsModel;
use Model\User\UserStatusModel;
use Paginator\PaginatedInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserManager
 *
 * @package Model
 */
class UserManager implements PaginatedInterface
{

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var PasswordEncoderInterface
     */
    protected $encoder;

    public function __construct(EventDispatcher $dispatcher, GraphManager $gm, PasswordEncoderInterface $encoder)
    {
        $this->dispatcher = $dispatcher;
        $this->gm = $gm;
        $this->encoder = $encoder;
    }

    /**
     * Returns an empty user instance
     *
     * @return User
     */
    public function createUser()
    {

        $user = new User();

        return $user;
    }

    /**
     * @param bool $includeGhosts
     * @return User[]
     * @throws Neo4jException
     */
    public function getAll($includeGhosts = false)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)');
        if (!$includeGhosts) {
            $qb->where('NOT (u:GhostUser)');
        }
        $qb->returns('u')
            ->orderBy('u.qnoow_id');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $return = array();

        foreach ($result as $row) {
            $return[] = $this->build($row);
        }

        return $return;
    }

    /**
     * @param $id
     * @param bool $includeGhost
     * @return User
     * @throws Neo4jException
     * @throws NotFoundHttpException
     */
    public function getById($id, $includeGhost = false)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', (int)$id);
        if (!$includeGhost) {
            $qb->where('NOT u:' . GhostUserManager::LABEL_GHOST_USER);
        }

        $qb->returns('u');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException(sprintf('User "%d" not found', $id));
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    /**
     * @param array $criteria
     * @return User
     * @throws Neo4jException
     * @throws NotFoundHttpException
     */
    public function findUserBy(array $criteria = array())
    {

        if (empty($criteria)) {
            throw new NotFoundHttpException('Criteria can not be empty');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)');

        $wheres = array();
        foreach ($criteria as $field => $value) {
            $wheres[] = 'u.' . $field . ' = { ' . $field . ' }';
        }
        $qb->where($wheres)
            ->setParameters($criteria)
            ->returns('u');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('User not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    /**
     * Finds a user by email
     *
     * @param string $email
     *
     * @return UserInterface
     */
    public function findUserByEmail($email)
    {
        return $this->findUserBy(array('emailCanonical' => $this->canonicalize($email)));
    }

    /**
     * Finds a user by username
     *
     * @param string $username
     *
     * @return UserInterface
     */
    public function findUserByUsername($username)
    {
        return $this->findUserBy(array('usernameCanonical' => $this->canonicalize($username)));
    }

    /**
     * Finds a user either by email, or username
     *
     * @param string $usernameOrEmail
     *
     * @return UserInterface
     */
    public function findUserByUsernameOrEmail($usernameOrEmail)
    {
        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->findUserByEmail($usernameOrEmail);
        }

        return $this->findUserByUsername($usernameOrEmail);
    }

    /**
     * Finds a user either by confirmation token
     *
     * @param string $token
     *
     * @return UserInterface
     */
    public function findUserByConfirmationToken($token)
    {
        return $this->findUserBy(array('confirmationToken' => $token));
    }

    public function validate(array $data, $isUpdate = false)
    {

        $errors = array();

        $metadata = $this->getMetadata($isUpdate);

        foreach ($metadata as $fieldName => $fieldData) {

            $fieldErrors = array();

            if (isset($fieldData['editable']) && $fieldData['editable'] === false) {
                continue;
            }

            if (!isset($data[$fieldName]) || !$data[$fieldName]) {
                if (isset($fieldData['required']) && $fieldData['required'] === true) {
                    $fieldErrors[] = sprintf('"%s" is required', $fieldName);
                }
            } else {

                $fieldValue = $data[$fieldName];

                switch ($fieldData['type']) {
                    case 'integer':
                        if (!is_integer($fieldValue)) {
                            $fieldErrors[] = sprintf('"%s" must be an integer', $fieldName);
                        }
                        break;
                    case 'string':
                        if (!is_string($fieldValue)) {
                            $fieldErrors[] = sprintf('"%s" must be an string', $fieldName);
                        }
                        break;
                    case 'boolean':
                        if ($fieldValue !== true && $fieldValue !== false) {
                            $fieldErrors[] = 'Must be a boolean.';
                        }
                        break;
                    case 'datetime':
                        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $fieldValue);
                        if (!($date && $date->format('Y-m-d H:i:s') == $fieldValue)) {
                            $fieldErrors[] = 'Invalid datetime format, valid format is "Y-m-d H:i:s".';
                        }
                        break;
                }
            }

            if (count($fieldErrors) > 0) {
                $errors[$fieldName] = $fieldErrors;
            }

        }

        $public = array();
        foreach ($metadata as $fieldName => $fieldData) {
            if (!(isset($fieldData['editable']) && $fieldData['editable'] === false)) {
                $public[$fieldName] = $fieldData;
            }
        }

        if ($isUpdate && !isset($data['userId'])) {
            $errors['userId'] = array('user ID is not defined');
        }

        if (isset($data['userId'])) {
            $public['userId'] = $data['userId'];
        }

        $diff = array_diff_key($data, $public);
        if (count($diff) > 0) {
            foreach ($diff as $invalidKey => $invalidValue) {
                $errors[$invalidKey] = array(sprintf('Invalid key "%s"', $invalidKey));
            }
        }

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param array $data
     * @return User
     * @throws Neo4jException
     */
    public function create(array $data)
    {

        $this->validate($data);

        $data['userId'] = $this->getNextId();

        $qb = $this->gm->createQueryBuilder();
        $qb->create('(u:User)')
            ->set('u.qnoow_id = { qnoow_id }')
            ->setParameter('qnoow_id', $data['userId'])
            ->set('u.status = { status }')
            ->setParameter('status', UserStatusModel::USER_STATUS_INCOMPLETE)
            ->set('u.createdAt = { createdAt }')
            ->setParameter('createdAt', (new \DateTime())->format('Y-m-d H:i:s'));

        $qb->getQuery()->getResultSet();

        $this->setDefaults($data);

        $user = $this->save($data);

        $this->dispatcher->dispatch(\AppEvents::USER_CREATED, new UserEvent($user));

        return $user;
    }

    /**
     * @param array $data
     * @return User
     */
    public function update(array $data)
    {
        $this->validate($data, true);

        $user = $this->save($data);

        $this->dispatcher->dispatch(\AppEvents::USER_UPDATED, new UserEvent($user));

        return $user;

    }

    /**
     * @param bool $includeGhost
     * @param integer $groupId
     * @return array
     * @throws Neo4jException
     */
    public function getAllCombinations($includeGhost = true, $groupId = null)
    {

        $conditions = array('u1.qnoow_id < u2.qnoow_id');
        if (!$includeGhost) {
            $conditions[] = 'NOT u1:' . GhostUserManager::LABEL_GHOST_USER;
            $conditions[] = 'NOT u2:' . GhostUserManager::LABEL_GHOST_USER;
        }
        $qb = $this->gm->createQueryBuilder();

        if ($groupId) {
            $qb->setParameter('groupId', (integer)$groupId);
            $qb->match('(g:Group)')
                ->where('id(g) = {groupId}')
                ->with('g')
                ->limit(1);
            $qb->match('(u1)-[:BELONGS_TO]-(g), (u2)-[:BELONGS_TO]-(g)');
        } else {
            $qb->match('(u1:User), (u2:User)');
        }
        $qb->where($conditions);

        $qb->returns('u1.qnoow_id, u2.qnoow_id');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $result;

    }

    /**
     * @param $id
     * @param int $limit
     * @return array
     * @throws Neo4jException
     */
    public function getByCommonLinksWithUser($id, $limit = 100)
    {

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters(array('limit' => (integer)$limit));

        $qb->match('(ref:User {qnoow_id: { id }})')
            ->setParameter('id', (integer)$id)
            ->match('(ref)-[:LIKES|DISLIKES]->(:Link)<-[l:LIKES]-(u:User)')
            ->where('NOT (ref.qnoow_id = u.qnoow_id)')
            ->with('u', 'count(l) as amount')
            ->orderBy('amount DESC')
            ->limit('{limit}')
            ->returns('DISTINCT u')
            ->orderBy('u.qnoow_id');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->parseResultSet($result);

    }

    /**
     * @param $questionId
     * @return array
     * @throws Neo4jException
     */
    public function getByQuestionAnswered($questionId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)-[:RATES]->(q:Question)')
            ->setParameter('questions', (integer)$questionId)
            ->where('id(q) IN [ { questions } ]')
            ->returns('DISTINCT u')
            ->orderBy('u.qnoow_id');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->parseResultSet($result);

    }

    /**
     * @param $groupId
     * @param array $data
     * @return User
     * @throws Neo4jException
     */
    public function getByGroup($groupId, array $data = array())
    {
        $qb = $this->gm->createQueryBuilder();

        $parameters = array('groupId' => $groupId);

        $qb->match('(g:Group)')
            ->where('id(g) = {groupId}')
            ->match('(u:User)-[:BELONGS_TO]->(g)');
        if (isset($data['userId'])) {
            $qb->where('NOT u.qnoow_id = {userId}');
            $parameters['userId'] = (integer)$data['userId'];
        }
        $qb->returns('u');
        if (isset($data['limit'])) {
            $parameters['limit'] = (integer)$data['limit'];
            $qb->limit('{limit}');
        }

        $qb->setParameters($parameters);

        $query = $qb->getQuery();

        return $this->parseResultSet($query->getResultSet());
    }

    public function getByCreatedGroup($groupId)
    {
        $qb = $this->gm->createQueryBuilder();

        $parameters = array('groupId' => $groupId);

        $qb->match('(g:Group)')
            ->where('id(g) = {groupId}')
            ->match('(u:User)-[:CREATED_GROUP]->(g)')
            ->returns('u')
            ->limit(1);
        $qb->setParameters($parameters);
        $result = $qb->getQuery()->getResultSet();

        return $this->build($result->current());
    }

    /**
     * @param $id
     * @return User
     * @throws Neo4jException
     */
    public function getOneByThread($id)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->match('(thread)<-[:HAS_THREAD]-(u:User)')
            ->returns('u');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            throw new NotFoundHttpException('Thread ' . $id . ' does not exist or is not from an user.');
        }

        return $this->build($result->current());
    }

    /**
     * @param SocialProfile $profile
     * @return User
     * @throws Neo4jException
     */
    public function getBySocialProfile(SocialProfile $profile)
    {
        $labels = array_keys(LookUpModel::$resourceOwners, $profile->getResource());

        if (empty($labels)) {
            $labels = array(LookUpModel::LABEL_SOCIAL_NETWORK);
        }

        foreach ($labels as $label) {
            $qb = $this->gm->createQueryBuilder();

            $qb->match("(sn:$label)")
                ->match('(u:User)-[hsn:HAS_SOCIAL_NETWORK]->(sn)')
                ->where('hsn.url = {url}');
            $qb->returns('u');

            $qb->setParameters(
                array(
                    'url' => $profile->getUrl(),
                )
            );

            $query = $qb->getQuery();
            $resultSet = $query->getResultSet();

            if ($resultSet->count() == 1) {
                $row = $resultSet->current();

                return $this->build($row);
            }
        }

        return null;
    }

    /**
     * @param $id
     * @return UserStatusModel
     * @throws NotFoundHttpException
     */
    public function getStatus($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', (integer)$id)
            ->returns('u.status AS status');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('User not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $row->offsetGet('status');

    }

    /**
     * @param $id1
     * @param $id2
     * @return UserComparedStatsModel
     * @throws \Exception
     */
    public function getComparedStats($id1, $id2)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters(
            array(
                'id1' => (integer)$id1,
                'id2' => (integer)$id2
            )
        );

        $qb->match('(u:User {qnoow_id: { id1 }}), (u2:User {qnoow_id: { id2 }})')
            ->optionalMatch('(u)-[:BELONGS_TO]->(g:Group)<-[:BELONGS_TO]-(u2)')
            ->with('u', 'u2', 'collect(distinct g) AS groupsBelonged')
            ->optionalmatch('(u)-[:TOKEN_OF]-(token:Token)')
            ->with('u', 'u2', 'groupsBelonged', 'collect(distinct token.resourceOwner) as resourceOwners')
            ->optionalmatch('(u2)-[:TOKEN_OF]-(token2:Token)');
        $qb->with('u, u2', 'groupsBelonged', 'resourceOwners', 'collect(distinct token2.resourceOwner) as resourceOwners2')
            ->optionalMatch('(u)-[:LIKES]->(link:Link)')
            ->where('(u2)-[:LIKES]->(link)')
            ->with('u', 'u2', 'groupsBelonged', 'resourceOwners', 'resourceOwners2', 'count(distinct(link)) AS commonContent')
            ->optionalMatch('(u)-[:ANSWERS]->(answer:Answer)')
            ->where('(u2)-[:ANSWERS]->(answer)')
            ->returns('groupsBelonged, resourceOwners, resourceOwners2, commonContent, count(distinct(answer)) as commonAnswers');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        $groups = array();
        foreach ($row->offsetGet('groupsBelonged') as $group) {
            /* @var $group Node */
            $groups[] = array(
                'id' => $group->getId(),
                'name' => $group->getProperty('name'),
                'html' => $group->getProperty('html'),
            );
        }

        $resourceOwners = array();
        foreach ($row->offsetGet('resourceOwners') as $resourceOwner) {
            $resourceOwners[] = $resourceOwner;
        }
        $resourceOwners2 = array();
        foreach ($row->offsetGet('resourceOwners2') as $resourceOwner2) {
            $resourceOwners2[] = $resourceOwner2;
        }

        $commonContent = $row->offsetGet('commonContent') ?: 0;
        $commonAnswers = $row->offsetGet('commonAnswers') ?: 0;

        $userStats = new UserComparedStatsModel(
            $groups,
            $resourceOwners,
            $resourceOwners2,
            $commonContent,
            $commonAnswers
        );

        return $userStats;
    }

    /**
     * @param integer $id
     * @param bool $set
     * @return UserStatusModel
     * @throws NotFoundHttpException
     */
    public function calculateStatus($id, $set = true)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', (integer)$id)
            ->optionalMatch('(u)-[:ANSWERS]->(a:Answer)')
            ->optionalMatch('(u)-[:LIKES]->(l:Link)')
            ->returns('u.status AS status', 'COUNT(DISTINCT a) AS answerCount', 'COUNT(DISTINCT l) AS linkCount');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException(sprintf('User "%d" not found', $id));
        }

        /* @var $row Row */
        $row = $result->current();

        $status = new UserStatusModel($row['status'], $row['answerCount'], $row['linkCount']);

        if ($status->getStatus() !== $row['status']) {
            $status->setStatusChanged();

            if ($set) {
                $qb = $this->gm->createQueryBuilder();
                $qb
                    ->match('(u:User {qnoow_id: { id }})')
                    ->setParameter('id', (integer)$id)
                    ->set('u.status = { status }')
                    ->setParameter('status', $status->getStatus())
                    ->returns('u');

                $query = $qb->getQuery();
                $query->getResultSet();
            }

        }

        return $status;
    }

    /**
     * @inheritdoc
     */
    public function validateFilters(array $filters)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function slice(array $filters, $offset, $limit)
    {
        $response = array();

        $parameters = array(
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        $profileQuery = "";
        if (isset($filters['profile'])) {
            $profileQuery = " MATCH (user)-[:PROFILE_OF]-(profile:Profile) ";
            if (isset($filters['profile']['zodiacSign'])) {
                $profileQuery .= "
                    MATCH (profile)-[:OPTION_OF]-(zodiacSign:ZodiacSign)
                    WHERE id(zodiacSign) = {zodiacSign}
                ";
                $parameters['zodiacSign'] = $filters['profile']['zodiacSign'];
            }
            if (isset($filters['profile']['gender'])) {
                $profileQuery .= "
                    MATCH (profile)-[:OPTION_OF]-(gender:Gender)
                    WHERE id(gender) = {gender}
                ";
                $parameters['gender'] = $filters['profile']['gender'];
            }
            if (isset($filters['profile']['orientation'])) {
                $profileQuery .= "
                    MATCH (profile)-[:OPTION_OF]-(orientation:Orientation)
                    WHERE id(orientation) = {orientation}
                ";
                $parameters['orientation'] = $filters['profile']['orientation'];
            }
        }

        $referenceUserQuery = "";
        $resultQuery = " RETURN user ";
        if (isset($filters['referenceUserId'])) {
            $parameters['referenceUserId'] = (integer)$filters['referenceUserId'];
            $referenceUserQuery = "
                MATCH
                (referenceUser:User)
                WHERE
                referenceUser.qnoow_id = {referenceUserId} AND
                user.qnoow_id <> {referenceUserId}
                OPTIONAL MATCH
                (user)-[match:MATCHES]-(referenceUser)
                OPTIONAL MATCH
                (user)-[similarity:SIMILARITY]-(referenceUser)
             ";
            $resultQuery .= ", match, similarity ";
        }

        $query = "
            MATCH
            (user:User)"
            . $profileQuery
            . $referenceUserQuery
            . $resultQuery
            . "
            SKIP {offset}
            LIMIT {limit}
            ;
         ";

        $contentQuery = $this->gm->createQuery($query, $parameters);

        $result = $contentQuery->getResultSet();

        foreach ($result as $row) {

            $user = $this->build($row);

            $user['matching'] = 0;
            if (isset($row['match'])) {
                $matchingByQuestions = $row['match']->getProperty('matching_questions');
                $user['matching'] = null === $matchingByQuestions ? 0 : $matchingByQuestions;
            }

            $user['similarity'] = 0;
            if (isset($row['similarity'])) {
                $similarity = $row['similarity']->getProperty('similarity');
                $user['similarity'] = null === $similarity ? 0 : $similarity;
            }

            $response[] = $user;
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function countTotal(array $filters)
    {

        $parameters = array();

        $queryWhere = '';
        if (isset($filters['referenceUserId'])) {
            $parameters['referenceUserId'] = (integer)$filters['referenceUserId'];
            $queryWhere .= " WHERE user.qnoow_id <> {referenceUserId} ";
        }

        if (isset($filters['profile'])) {
            //TODO: Profile filters
        }

        $query = "
            MATCH
            (user:User)
            " . $queryWhere . "
            RETURN
            count(user) as total
            ;
        ";

        $contentQuery = $this->gm->createQuery($query, $parameters);

        $result = $contentQuery->getResultSet();
        $row = $result->current();
        $count = $row['total'];

        return $count;
    }

    public function getMetadata($isUpdate = false)
    {
        $metadata = array(
            'qnoow_id' => array('type' => 'string', 'editable' => false),
            'username' => array('type' => 'string', 'required' => true, 'editable' => true),
            'usernameCanonical' => array('type' => 'string', 'editable' => false),
            'email' => array('type' => 'string', 'required' => true),
            'emailCanonical' => array('type' => 'string', 'editable' => false),
            'enabled' => array('type' => 'boolean', 'default' => true),
            'salt' => array('type' => 'string', 'editable' => false),
            'password' => array('type' => 'string', 'editable' => false),
            'plainPassword' => array('type' => 'string', 'required' => true, 'visible' => false),
            'lastLogin' => array('type' => 'datetime'),
            'locked' => array('type' => 'boolean', 'default' => false),
            'expired' => array('type' => 'boolean', 'editable' => false),
            'expiresAt' => array('type' => 'datetime'),
            'confirmationToken' => array('type' => 'string'),
            'passwordRequestedAt' => array('type' => 'datetime'),
            'facebookID' => array('type' => 'string'),
            'googleID' => array('type' => 'string'),
            'twitterID' => array('type' => 'string'),
            'spotifyID' => array('type' => 'string'),
            'createdAt' => array('type' => 'datetime', 'editable' => false),
            'updatedAt' => array('type' => 'datetime', 'editable' => false),
            'confirmed' => array('type' => 'boolean', 'default' => false),
            'status' => array('type' => 'string', 'editable' => false),
            'picture' => array('type' => 'string'),
        );

        if ($isUpdate) {
            $metadata['plainPassword']['required'] = false;
        }

        return $metadata;
    }

    public function save(array $data)
    {
        $userId = $data['userId'];
        unset($data['userId']);

        $this->updateCanonicalFields($data);
        $this->updatePassword($data);
        $this->updatePicture($data);

        $data['updatedAt'] = (new \DateTime())->format('Y-m-d H:i:s');

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', $userId)
            ->with('u');

        foreach ($data as $key => $value) {
            $qb->set("u.$key = { $key }")
                ->setParameter($key, $value);
        }

        $qb->returns('u');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function fuseUsers($userId1, $userId2)
    {
        return $this->gm->fuseNodes($this->getNodeId($userId1), $this->getNodeId($userId2));
    }

    public function build(Row $row)
    {

        /* @var $node Node */
        $node = $row->offsetGet('u');
        $properties = $node->getProperties();
        if (isset($properties['qnoow_id'])) {
            $properties['id'] = $properties['qnoow_id'];
            unset($properties['qnoow_id']);
        }
        $metadata = $this->getMetadata();
        $user = $this->createUser();

        foreach ($properties as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($user, $method)) {
                if (isset($metadata[$key]['type']) && $metadata[$key]['type'] === 'datetime') {
                    $value = new \DateTime($value);
                }
                $user->{$method}($value);
            }
        }

        return $user;
    }

    public function getNextId()
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->returns('u.qnoow_id AS id')
            ->orderBy('id DESC')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $id = 1;
        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            $id = $row->offsetGet('qnoow_id') + 1;
        }

        return $id;
    }

    public function canonicalize($string)
    {
        return null === $string ? null : mb_convert_case($string, MB_CASE_LOWER, mb_detect_encoding($string));
    }

    public function isChannel($userId, $resource)
    {
        $channelLabel = $this->buildChannelLabel($resource);
        $labels = $this->getLabelsFromId($userId);

        if (in_array($channelLabel, $labels)) {
            return true;
        }

        return false;
    }

    public function setAsChannel($userId, $resource)
    {
        $channelLabel = $this->buildChannelLabel($resource);

        return $this->setLabel($userId, $channelLabel);
    }

    protected function buildChannelLabel($resource = null)
    {
        if (in_array($resource, TokensModel::getResourceOwners())) {
            return 'Channel' . ucfirst($resource);
        }

        return null;
    }

    protected function getLabelsFromId($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', (int)$id);

        $qb->returns('labels(u) as labels');

        $rs = $qb->getQuery()->getResultSet();

        if ($rs->count() == 0) {
            throw new NotFoundHttpException('User to get labels from not found');
        }

        $labelsRow = $rs->current()->offsetGet('labels');
        $labels = array();
        foreach ($labelsRow as $label) {
            $labels[] = $label;
        }

        return $labels;
    }

    protected function setLabel($id, $label)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', (int)$id);
        $qb->set("u :$label");

        $qb->returns('u');

        $rs = $qb->getQuery()->getResultSet();

        if ($rs->count() == 0) {
            throw new NotFoundHttpException(sprintf('User to set label %s not found', $label));
        }

        return $this->build($rs->current());
    }

    /**
     * @param $resultSet
     * @return array
     */
    protected function parseResultSet($resultSet)
    {
        $users = array();
        foreach ($resultSet as $row) {
            $users[] = $this->build($row);
        }

        return $users;

    }

    protected function setDefaults(array &$user)
    {
        foreach ($this->getMetadata() as $fieldName => $fieldData) {
            if (!array_key_exists($fieldName, $user) && isset($fieldData['default'])) {
                $user[$fieldName] = $fieldData['default'];
            }
        }
    }

    protected function updateCanonicalFields(array &$user)
    {
        if (isset($user['username']) && !isset($user['usernameCanonical'])) {
            $user['usernameCanonical'] = $this->canonicalize($user['username']);
        }
        if (isset($user['email']) && !isset($user['emailCanonical'])) {
            $user['emailCanonical'] = $this->canonicalize($user['email']);
        }
    }

    protected function updatePassword(array &$user)
    {

        if (isset($user['plainPassword'])) {
            $user['salt'] = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
            $user['password'] = $this->encoder->encodePassword($user['plainPassword'], $user['salt']);
            unset($user['plainPassword']);
        }
    }

    protected function updatePicture(array &$user)
    {

        if (isset($user['picture']) && filter_var($user['picture'], FILTER_VALIDATE_URL)) {
            $url = $user['picture'];
            $user['picture'] = $user['usernameCanonical'] . '_' . time() . '.jpg';
            $filename = __DIR__ . '/../../../social/web/user/images/' . $user['picture'];
            file_put_contents($filename, file_get_contents($url));
        }
    }

    private function getNodeId($userId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User{qnoow_id: {id}})')
            ->setParameter('id', (integer)$userId)
            ->returns('id(u) as id')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('User with id ' . $userId . ' not found');
        }

        $id = $result->current()->offsetGet('id');

        return $id;
    }
}
