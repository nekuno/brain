<?php

namespace Model;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\Neo4j\Neo4jException;
use Model\User\RelationsModel;
use Model\User\UserStatsModel;
use Model\User\UserStatusModel;
use Paginator\PaginatedInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserModel
 *
 * @package Model
 */
class UserModel implements PaginatedInterface
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var Connection
     */
    protected $connectionSocial;

    /**
     * @var EntityManager
     */
    protected $entityManagerBrain;

    /**
     * @var RelationsModel
     */
    protected $relationsModel;

    /**
     * @var array
     */
    protected $metadata;

    /**
     * @var string
     */
    protected $defaultLocale;

    public function __construct(GraphManager $gm, Connection $connectionSocial, EntityManager $entityManagerBrain, RelationsModel $relationsModel, array $metadata, $defaultLocale)
    {
        $this->gm = $gm;
        $this->connectionSocial = $connectionSocial;
        $this->entityManagerBrain = $entityManagerBrain;
        $this->relationsModel = $relationsModel;
        $this->metadata = $metadata;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @return array
     * @throws Neo4jException
     */
    public function getAll()
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->returns('u')
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
     * @return array
     * @throws Neo4jException
     */
    public function getById($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', (int)$id)
            ->returns('u');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException(sprintf('User "%d" not found', $id));
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function validate(array $data)
    {

        $errors = array();

        $metadata = $this->getMetadata();

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
        foreach ($metadata as $name => $item) {
            if (!(isset($item['editable']) && $item['editable'] === false)) {
                $public[$name] = $item;
            }
        }

        $diff = array_diff_key($data, $public);
        if (count($diff) > 0) {
            foreach ($diff as $invalidKey => $invalidValue) {
                $errors[$invalidKey] = array(sprintf('Invalid key "%s"', $invalidKey));
            }
        }

        if (count($errors) > 0) {
            $e = new ValidationException('Validation error');
            $e->setErrors($errors);
            throw $e;
        }
    }

    public function create(array $data)
    {

        $this->validate($data);

        $id = $this->getNextId();

        $qb = $this->gm->createQueryBuilder();
        $qb->create('(u:User {qnoow_id: { qnoow_id }, status: { status }, username: { username }, email: { email }})')
            ->setParameter('qnoow_id', $id)
            ->setParameter('status', UserStatusModel::USER_STATUS_INCOMPLETE)
            ->setParameter('username', $data['username'])
            ->setParameter('email', $data['email'])
            ->returns('u');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    /**
     * @param array $user
     */
    public function update(array $user)
    {

    }

    /**
     * @param null $id
     * @return array
     * @throws \Exception
     */
    public function remove($id = null)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { id }})')
            ->delete('u')
            ->setParameter('id', $id);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->parseResultSet($result);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAllCombinations()
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u1:User), (u2:User)')
            ->where('u1.qnoow_id < u2.qnoow_id')
            ->returns('u1.qnoow_id, u2.qnoow_id');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $result;

    }

    /**
     * @param null $id
     * @return array
     * @throws \Exception
     */
    public function getByCommonLinksWithUser($id = null)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(ref:User {qnoow_id: { id }})')
            ->setParameter('id', (integer)$id)
            ->match('(ref)-[:LIKES|DISLIKES]->(:Link)<-[:LIKES]-(u:User)')
            ->returns('DISTINCT u')
            ->orderBy('u.qnoow_id');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->parseResultSet($result);

    }

    /**
     * @param $questionId
     * @return array
     * @throws \Exception
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
     * @throws \Exception
     * @return array
     */
    public function getByGroup($groupId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group{id:{groupId}})')
            ->match('(u:User)-[:BELONGS_TO]->(g)');
        $qb->returns('u');

        $qb->setParameters(
            array(
                'groupId' => $groupId
            )
        );

        $query = $qb->getQuery();

        return $this->parseResultSet($query->getResultSet());
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

    public function getStats($id)
    {

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', (integer)$id)
            ->with('u')
            ->optionalMatch('(u)-[r:LIKES]->(:Link)')
            ->with('u,count(r) AS contentLikes')
            ->optionalMatch('(u)-[r:LIKES]->(:Video)')
            ->with('u,contentLikes,count(r) AS videoLikes')
            ->optionalMatch('(u)-[r:LIKES]->(:Audio)')
            ->with('u,contentLikes,videoLikes,count(r) AS audioLikes')
            ->optionalMatch('(u)-[r:LIKES]->(:Image)')
            ->with('u,contentLikes,videoLikes,audioLikes,count(r) AS imageLikes')
            ->optionalMatch('(u)-[:BELONGS_TO]->(g:Group)')
            ->with('u,contentLikes, videoLikes, audioLikes, imageLikes, collect(g) AS groupsBelonged')
            ->optionalMatch('(u)-[r:ANSWERS]->(:Answer)')
            ->returns('contentLikes', 'videoLikes', 'audioLikes', 'imageLikes', 'groupsBelonged', 'count(r) AS questionsAnswered', 'u.available_invitations AS available_invitations');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('User not found');
        }

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

        $numberOfReceivedLikes = $this->relationsModel->countTo($id, RelationsModel::LIKES);
        $numberOfUserLikes = $this->relationsModel->countFrom($id, RelationsModel::LIKES);

        $dataStatusRepository = $this->entityManagerBrain->getRepository('\Model\Entity\DataStatus');

        $twitterStatus = $dataStatusRepository->findOneBy(array('userId' => (int)$id, 'resourceOwner' => 'twitter'));
        $facebookStatus = $dataStatusRepository->findOneBy(array('userId' => (int)$id, 'resourceOwner' => 'facebook'));
        $googleStatus = $dataStatusRepository->findOneBy(array('userId' => (int)$id, 'resourceOwner' => 'google'));
        $spotifyStatus = $dataStatusRepository->findOneBy(array('userId' => (int)$id, 'resourceOwner' => 'spotify'));

        $userStats = new UserStatsModel(
            $row->offsetGet('contentLikes'),
            $row->offsetGet('videoLikes'),
            $row->offsetGet('audioLikes'),
            $row->offsetGet('imageLikes'),
            (integer)$numberOfReceivedLikes,
            (integer)$numberOfUserLikes,
            $groups,
            $row->offsetGet('questionsAnswered'),
            !empty($twitterStatus) ? (boolean)$twitterStatus->getFetched() : false,
            !empty($twitterStatus) ? (boolean)$twitterStatus->getProcessed() : false,
            !empty($facebookStatus) ? (boolean)$facebookStatus->getFetched() : false,
            !empty($facebookStatus) ? (boolean)$facebookStatus->getProcessed() : false,
            !empty($googleStatus) ? (boolean)$googleStatus->getFetched() : false,
            !empty($googleStatus) ? (boolean)$googleStatus->getProcessed() : false,
            !empty($spotifyStatus) ? (boolean)$spotifyStatus->getFetched() : false,
            !empty($spotifyStatus) ? (boolean)$spotifyStatus->getProcessed() : false,
            $row->offsetGet('available_invitations')
        );

        return $userStats;

    }

    /**
     * @param $id1
     * @param $id2
     * @return UserStatsModel
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
            ->match('(u)-[:BELONGS_TO]->(g:Group)<-[:BELONGS_TO]-(u2)');
        //TODO: Add stats comparation to fill returned UserStatsModel
        $qb->returns('collect(g) AS groupsBelonged');

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

        $userStats = new UserStatsModel(
            null,
            null,
            null,
            null,
            null,
            null,
            $groups,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        return $userStats;
    }

    /**
     * @param integer $id
     * @return UserStatusModel
     * @throws NotFoundHttpException
     */
    public function calculateStatus($id)
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

        $this->connectionSocial->update('users', array('status' => $status->getStatus()), array('id' => (integer)$id));

        return $status;
    }

    /**
     * @param null $locale
     * @param array $dynamicFilters User-dependent filters, not set in this model
     * @param bool $filter Filter non-public attributes
     * @return array
     */
    public function getFilters($locale = null, $dynamicFilters = array(), $filter = true)
    {
        $locale = $this->getLocale($locale);
        $metadata = $this->getFiltersMetadata($locale, $dynamicFilters, $filter);

        foreach ($dynamicFilters['groups'] as $group) {
            $metadata['groups']['choices'][$group['id']] = $group['name'];
        }

        foreach ($metadata as $key => &$item) {
            if (isset($item['labelFilter'])) {
                $item['label'] = $item['labelFilter'][$locale];
                unset($item['labelFilter']);
            }
            if (isset($item['filterable']) && $item['filterable'] === false) {
                unset($metadata[$key]);
            }
        }

        //check user-dependent choices existence for not showing up to user

        if ($dynamicChoices['groups'] = null || $dynamicFilters['groups'] == array()) {
            unset($metadata['groups']);
        }

        return $metadata;
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
            (user:User)
            WHERE
            user.status = 'complete'"
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
            $user = array();

            $user['id'] = $row['user']->getProperty('qnoow_id');
            $user['username'] = $row['content']->getProperty('username');
            $user['email'] = $row['content']->getProperty('email');

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

        $queryWhere = " WHERE user.status = 'complete' ";
        if (isset($filters['referenceUserId'])) {
            $parameters['referenceUserId'] = (integer)$filters['referenceUserId'];
            $queryWhere .= " AND user.qnoow_id <> {referenceUserId} ";
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

    public function getMetadata()
    {
        return array(
            'qnoow_id' => array('type' => 'string', 'editable' => false),
            'username' => array('type' => 'string', 'required' => true, 'editable' => true),
            'username_canonical' => array('type' => 'string', 'editable' => false),
            'email' => array('type' => 'string', 'required' => true),
            'email_canonical' => array('type' => 'string', 'editable' => false),
            'enabled' => array('type' => 'boolean', 'required' => true),
            'salt' => array('type' => 'string', 'required' => true),
            'password' => array('type' => 'string', 'required' => true),
            'lastLogin' => array('type' => 'datetime', 'editable' => false),
            'locked' => array('type' => 'boolean', 'required' => true),
            'expired' => array('type' => 'boolean', 'editable' => false),
            'expiresAt' => array('type' => 'datetime', 'required' => false),
            'confirmationToken' => array('type' => 'string', 'editable' => false),
            'passwordRequestedAt' => array('type' => 'datetime', 'editable' => false),
            'facebookID' => array('type' => 'string', 'required' => false),
            'googleID' => array('type' => 'string', 'required' => false),
            'twitterID' => array('type' => 'string', 'required' => false),
            'spotifyID' => array('type' => 'string', 'required' => false),
            'createdAt' => array('type' => 'datetime', 'editable' => false),
            'updatedAt' => array('type' => 'datetime', 'editable' => false),
            'confirmed' => array('type' => 'boolean', 'required' => true),
            'status' => array('type' => 'string', 'editable' => false),
        );
    }

    /**
     * @param null $locale
     * @param array $dynamicChoices user-dependent choices (cannot be set from this model)
     * @param bool $filter
     * @return array
     */
    protected function getFiltersMetadata($locale = null, array $dynamicChoices = array(), $filter = true)
    {

        $locale = $this->getLocale($locale);

        $publicMetadata = $dynamicChoices;
        $choiceOptions = $this->getChoiceOptions();

        foreach ($this->metadata as $name => $values) {
            $publicField = $values;
            $publicField['label'] = $values['label'][$locale];

            if ($values['type'] === 'choice') {
                $publicField['choices'] = array();
                if (isset($choiceOptions[$name])) {
                    $publicField['choices'] = $choiceOptions[$name];
                }
            } elseif ($values['type'] === 'tags') {
                $publicField['top'] = $this->getTopUserTags($name);
            }

            $publicMetadata[$name] = $publicField;
        }

        if ($filter) {
            foreach ($publicMetadata as &$item) {
                if (isset($item['labelFilter'])) {
                    unset($item['labelFilter']);
                }
                if (isset($item['filterable'])) {
                    unset($item['filterable']);
                }
            }
        }

        return $publicMetadata;
    }

    protected function getLocale($locale)
    {

        if (!$locale || !in_array($locale, array('en', 'es'))) {
            $locale = $this->defaultLocale;
        }

        return $locale;
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

    protected function build(Row $row)
    {

        /* @var $node Node */
        $node = $row->offsetGet('u');
        $properties = $node->getProperties();

        $ordered = array();
        foreach (array_keys($this->getMetadata()) as $key) {
            if (array_key_exists($key, $properties)) {
                $ordered[$key] = $properties[$key];
                unset($properties[$key]);
            } else {
                $ordered[$key] = null;
            }
        }

        return $ordered + $properties;
    }

    /** Returns statically defined options
     * @return array
     */
    protected function getChoiceOptions()
    {
        return array();
    }

    /** Returns User tags to use when created user tags
     * @param $name
     * @return array
     */
    protected function getTopUserTags($type)
    {
        return array();
    }

    protected function getNextId()
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
}
