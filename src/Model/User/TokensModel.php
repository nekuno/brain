<?php

namespace Model\User;

use Doctrine\DBAL\Connection;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokensModel
{

    CONST FACEBOOK = 'facebook';
    CONST TWITTER = 'twitter';
    CONST GOOGLE = 'google';
    CONST SPOTIFY = 'spotify';

    /**
     * @var GraphManager
     */
    protected $gm;
    /**
     * @var Connection
     */
    protected $connectionSocial;

    public function __construct(GraphManager $graphManager, Connection $connectionSocial)
    {
        $this->gm = $graphManager;
        // TODO: Refactor and remove this dependency
        $this->connectionSocial = $connectionSocial;
    }

    public static function getResourceOwners()
    {
        return array(
            self::FACEBOOK,
            self::TWITTER,
            self::GOOGLE,
            self::SPOTIFY,
        );
    }

    public function getAll($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[:TOKEN_OF]-(token:Token)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', (integer)$id)
            ->returns('user', 'token');

        $result = $qb->getQuery()->getResultSet();

        $return = array();
        /* @var $row Row */
        foreach ($result as $row) {
            $return[] = $this->build($row);
        }

        return $return;
    }

    /**
     * @param int $id
     * @param string $resourceOwner
     * @return array
     * @throws NotFoundHttpException
     */
    public function getById($id, $resourceOwner)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[:TOKEN_OF]-(token:Token)')
            ->where('user.qnoow_id = { id }', 'token.resourceOwner = { resourceOwner }')
            ->setParameter('id', (integer)$id)
            ->setParameter('resourceOwner', $resourceOwner)
            ->returns('user', 'token')
            ->limit(1);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Token not found');
        }

        /* @var $row Row */
        $row = $result->current();

        $token = $this->build($row);

        return $token;
    }

    /**
     * @param int $id
     * @param string $resourceOwner
     * @param array $data
     * @return array
     * @throws ValidationException|NotFoundHttpException|MethodNotAllowedHttpException
     */
    public function create($id, $resourceOwner, array $data)
    {

        list($userNode, $tokenNode) = $this->getUserAndTokenNodesById($id, $resourceOwner);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if ($tokenNode instanceof Node) {
            throw new MethodNotAllowedHttpException(array('PUT'), 'Token already exists');
        }

        $this->validate($resourceOwner, $data);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', (integer)$id)
            ->merge('(user)<-[:TOKEN_OF]-(token:Token {createdTime: { createdTime }})')
            ->setParameter('createdTime', time())
            ->returns('user', 'token');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();
        $tokenNode = $row->offsetGet('token');

        $this->saveTokenData($tokenNode, $resourceOwner, $data);

        return $this->getById($id, $resourceOwner);
    }

    /**
     * @param int $id
     * @param string $resourceOwner
     * @param array $data
     * @return array
     * @throws ValidationException|NotFoundHttpException
     */
    public function update($id, $resourceOwner, array $data)
    {

        list($userNode, $tokenNode) = $this->getUserAndTokenNodesById($id, $resourceOwner);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if (!($tokenNode instanceof Node)) {
            throw new NotFoundHttpException('Token not found');
        }

        $this->validate($resourceOwner, $data);

        $this->saveTokenData($tokenNode, $resourceOwner, $data);

        return $this->getById($id, $resourceOwner);
    }

    /**
     * @param int $id
     * @param string $resourceOwner
     */
    public function remove($id, $resourceOwner)
    {

        list($userNode, $tokenNode) = $this->getUserAndTokenNodesById($id, $resourceOwner);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if (!($tokenNode instanceof Node)) {
            throw new NotFoundHttpException('Token not found');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[token_of:TOKEN_OF]-(token:Token)')
            ->where('user.qnoow_id = { id }', 'token.resourceOwner = { resourceOwner }')
            ->setParameter('id', (integer)$id)
            ->setParameter('resourceOwner', $resourceOwner)
            ->delete('token', 'token_of');

        $query = $qb->getQuery();
        $query->getResultSet();
    }

    /**
     * @param string $resourceOwner
     * @param array $data
     * @throws ValidationException
     */
    public function validate($resourceOwner, array $data)
    {

        $errors = array();

        if (!$resourceOwner || !in_array($resourceOwner, self::getResourceOwners())) {
            $errors['resourceOwner'] = array(sprintf('resourceOwner not valid, valid values are "%s"', implode('", "', self::getResourceOwners())));
        }

        $metadata = array(
            'oauthToken' => array('type' => 'string', 'required' => true),
            'oauthTokenSecret' => array('type' => 'string', 'required' => false),
            'createdTime' => array('type' => 'integer', 'required' => false),
            'expireTime' => array('type' => 'integer', 'required' => false),
            'refreshToken' => array('type' => 'string', 'required' => false),
        );

        foreach ($metadata as $fieldName => $fieldData) {

            $fieldErrors = array();

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
                }
            }

            if (count($fieldErrors) > 0) {
                $errors[$fieldName] = $fieldErrors;
            }

        }

        $diff = array_diff_key($data, $metadata);
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

    /**
     * @param int $id User id
     * @param string $resourceOwner Resource owner
     * @return array
     */
    public function getByUserOrResource($id = null, $resourceOwner = null)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[:TOKEN_OF]-(token:Token)');

        if ($id || $resourceOwner) {

            $wheres = array();
            if ($id) {
                $qb->setParameter('id', (integer)$id);
                $wheres[] = 'user.qnoow_id = { id }';
            }
            if ($resourceOwner) {
                $qb->setParameter('resourceOwner', $resourceOwner);
                $wheres[] = 'token.resourceOwner = { resourceOwner }';
            }

            $qb->where($wheres);

        }
        $qb->returns('user', 'token')
            ->orderBy('user.qnoow_id', 'token.resourceOwner');

        $result = $qb->getQuery()->getResultSet();

        $return = array();
        /* @var $row Row */
        foreach ($result as $row) {
            /* @var $user Node */
            $user = $row->offsetGet('user');
            $userId = $user->getProperty('qnoow_id');
            $ids = $this->connectionSocial
                ->createQueryBuilder()
                ->select('facebookID', 'googleID', 'twitterID', 'spotifyID')
                ->from('users')
                ->where('id = :id')
                ->setParameter(':id', $userId)
                ->execute()
                ->fetch();

            $return[] = array_merge($this->build($row), $ids);
        }

        return $return;
    }

    protected function build(Row $row)
    {
        /* @var $user Node */
        $user = $row->offsetGet('user');
        /* @var $node Node */
        $node = $row->offsetGet('token');
        $token = $node->getProperties();
        ksort($token);

        return array_merge(array('id' => $user->getProperty('qnoow_id')), $token);
    }

    protected function getUserAndTokenNodesById($id, $resourceOwner)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', (integer)$id)
            ->optionalMatch('(user)<-[:TOKEN_OF]-(token:Token)')
            ->where('token.resourceOwner = { resourceOwner }')
            ->setParameter('resourceOwner', $resourceOwner)
            ->returns('user', 'token')
            ->limit(1);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) < 1) {
            return array(null, null);
        }

        /* @var Row $row */
        $row = $result->current();
        $userNode = $row->offsetGet('user');
        $tokenNode = $row->offsetGet('token');

        return array($userNode, $tokenNode);
    }

    protected function saveTokenData(Node $tokenNode, $resourceOwner, array $data)
    {

        $tokenNode->setProperty('resourceOwner', $resourceOwner);
        $tokenNode->setProperty('updatedTime', time());
        foreach ($data as $property => $value) {
            $tokenNode->setProperty($property, $value);
        }

        return $tokenNode->save();
    }

}