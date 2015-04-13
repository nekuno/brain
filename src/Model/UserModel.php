<?php

namespace Model;

use Doctrine\DBAL\Connection;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\User\ProfileModel;
use Model\User\UserStatsModel;
use Model\User\UserStatusModel;
use Paginator\PaginatedInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManager;

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
     * @var ProfileModel
     */
    protected $pm;

    /**
     * @var Connection
     */
    protected $driver;

    /**
     * @var EntityManager
     */
    protected $em;


    public function __construct(GraphManager $gm, ProfileModel $pm, Connection $driver, EntityManager $entityManager)
    {
        $this->gm = $gm;
        $this->pm = $pm;
        $this->driver = $driver;
        $this->em = $entityManager;
    }

    /**
     * Creates an new User and returns the query result
     *
     * @param array $user
     * @throws \Exception
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function create(array $user = array())
    {
        if (!isset($user['email'])) {
            $user['email'] = '';
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->create('(u:User {qnoow_id: { qnoow_id }, status: { status }, username: { username }, email: { email }})')
            ->setParameter('qnoow_id', $user['id'])
            ->setParameter('status', UserStatusModel::USER_STATUS_INCOMPLETE)
            ->setParameter('username', $user['username'])
            ->setParameter('email', $user['email'])
            ->returns('u');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $this->pm->create($user['id'], array());

        return $this->parseResultSet($result);
    }

    /**
     * @param array $user
     */
    public function update(array $user = array())
    {
        // TODO: do your magic here
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
    public function getAll()
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->returns('u')
            ->orderBy('u.qnoow_id');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->parseResultSet($result);

    }

    public function getById($id)
    {

        if (!$id) {
            throw new NotFoundHttpException('User not found');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', (integer)$id)
            ->returns('u');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('User not found');
        }

        return $this->parseRow($result->current());

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
            ->optionalMatch('(u)-[r:ANSWERS]->(:Answer)')
            ->returns('contentLikes', 'videoLikes', 'audioLikes', 'imageLikes', 'count(r) AS questionsAnswered');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('User not found');
        }

        /* @var $row Row */
        $row = $result->current();

        $numberOfReceivedLikes = $this->driver->executeQuery('SELECT COUNT(*) AS numberOfReceivedLikes FROM user_like WHERE user_to = :user_to', array('user_to' => (integer)$id))->fetchColumn();
        $numberOfUserLikes = $this->driver->executeQuery('SELECT COUNT(*) AS numberOfUserLikes FROM user_like WHERE user_from = :user_from', array('user_from' => (integer)$id))->fetchColumn();

        $dataStatusRepository = $this->em->getRepository('\Model\Entity\DataStatus');

        $twitterStatus = $dataStatusRepository->findOneBy(array('userId' => (int)$id, 'resourceOwner' => 'twitter'));
        $facebookStatus = $dataStatusRepository->findOneBy(array('userId' => (int)$id, 'resourceOwner' => 'facebook'));
        $googleStatus = $dataStatusRepository->findOneBy(array('userId' => (int)$id, 'resourceOwner' => 'google'));
        $spotifyStatus = $dataStatusRepository->findOneBy(array('userId' => (int)$id, 'resourceOwner' => 'spotify'));

        $userStats = new UserStatsModel(
            $row->offsetGet('contentLikes'),
            $row->offsetGet('videoLikes'),
            $row->offsetGet('audioLikes'),
            $row->offsetGet('imageLikes'),
            $row->offsetGet('questionsAnswered'),
            (integer)$numberOfReceivedLikes,
            (integer)$numberOfUserLikes,
            !empty($twitterStatus) ? (boolean)$twitterStatus->getFetched() : false,
            !empty($twitterStatus) ? (boolean)$twitterStatus->getProcessed() : false,
            !empty($facebookStatus) ? (boolean)$facebookStatus->getFetched() : false,
            !empty($facebookStatus) ? (boolean)$facebookStatus->getProcessed() : false,
            !empty($googleStatus) ? (boolean)$googleStatus->getFetched() : false,
            !empty($googleStatus) ? (boolean)$googleStatus->getProcessed() : false,
            !empty($spotifyStatus) ? (boolean)$spotifyStatus->getFetched() : false,
            !empty($spotifyStatus) ? (boolean)$spotifyStatus->getProcessed() : false
        );

        return $userStats;

    }

    /**
     * @param integer $id
     * @return UserStatusModel
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
            throw new NotFoundHttpException('User not found');
        }

        /* @var $row Row */
        $row = $result->current();

        $status = new UserStatusModel($row['status'], $row['answerCount'], $row['linkCount']);

        if ($status->getStatus() !== $row['status']) {

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

        $this->driver->update('users', array('status' => $status->getStatus()), array('id' => (integer)$id));

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

    /**
     * @param $resultSet
     * @return array
     */
    private function parseResultSet($resultSet)
    {
        $users = array();

        foreach ($resultSet as $row) {
            $users[] = $this->parseRow($row);
        }

        return $users;

    }

    private function parseRow($row)
    {
        return array(
            'qnoow_id' => $row['u']->getProperty('qnoow_id'),
            'username' => $row['u']->getProperty('username'),
            'email' => $row['u']->getProperty('email'),
        );
    }
}
