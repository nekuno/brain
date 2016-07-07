<?php

namespace Model\User;

use Doctrine\ORM\EntityManager;
use Event\AccountConnectEvent;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use HWI\Bundle\OAuthBundle\DependencyInjection\Configuration;
use Model\Entity\DataStatus;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\User\SocialNetwork\SocialProfile;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokensModel
{

    CONST FACEBOOK = 'facebook';
    CONST TWITTER = 'twitter';
    CONST GOOGLE = 'google';
    CONST SPOTIFY = 'spotify';
    CONST LINKEDIN = 'linkedin';

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var EntityManager
     */
    protected $entityManagerBrain;

    public function __construct(EventDispatcher $dispatcher, GraphManager $graphManager, EntityManager $entityManagerBrain)
    {
        $this->dispatcher = $dispatcher;
        $this->gm = $graphManager;
        $this->entityManagerBrain = $entityManagerBrain;
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

        $this->validate($id, $resourceOwner, $data);

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

        $this->saveTokenData($userNode, $tokenNode, $resourceOwner, $data);

        $this->dispatcher->dispatch(\AppEvents::ACCOUNT_CONNECTED, new AccountConnectEvent($id, $resourceOwner));

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

        $this->validate($id, $resourceOwner, $data);

        $this->saveTokenData($userNode, $tokenNode, $resourceOwner, $data);

        return $this->getById($id, $resourceOwner);
    }

    /**
     * @param int $id
     * @param string $resourceOwner
     * @return array
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

        $token = $this->getById($id, $resourceOwner);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[token_of:TOKEN_OF]-(token:Token)')
            ->where('user.qnoow_id = { id }', 'token.resourceOwner = { resourceOwner }')
            ->setParameter('id', (integer)$id)
            ->setParameter('resourceOwner', $resourceOwner)
            ->delete('token', 'token_of')
            ->returns('COUNT(token_of) AS count');

        $query = $qb->getQuery();
        $result = $query->getResultSet();
        /* @var $row Row */
        $row = $result->current();
        $count = $row->offsetGet('count');

        if ($count === 1) {
            $repository = $this->entityManagerBrain->getRepository('\Model\Entity\DataStatus');
            $dataStatus = $repository->findOneBy(array('userId' => $id, 'resourceOwner' => $resourceOwner));

            if ($dataStatus instanceof DataStatus) {
                $this->entityManagerBrain->remove($dataStatus);
                $this->entityManagerBrain->flush();
            }
        }

        return $token;
    }

    /**
     * @param string $id
     * @param string $resourceOwner
     * @param array $data
     * @throws ValidationException
     */
    public function validate($id, $resourceOwner, array $data)
    {

        $errors = array();

        $data['resourceOwner'] = $resourceOwner;

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
                    case 'choice':
                        $choices = $fieldData['choices'];
                        if (!in_array($fieldValue, $choices)) {
                            $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $fieldValue, implode("', '", $choices));
                        }
                        break;
                }

                if ($fieldName === 'resourceId') {
                    $qb = $this->gm->createQueryBuilder();
                    $qb->match('(user:User)')
                        ->where('user.qnoow_id <> { id } AND user.' . $resourceOwner . 'ID = { resourceId }')
                        ->setParameter('id', (integer)$id)
                        ->setParameter('resourceId', $fieldValue)
                        ->returns('user');

                    $query = $qb->getQuery();

                    $result = $query->getResultSet();

                    if ($result->count() > 0) {
                        $fieldErrors[] = 'There is other user with the same resourceId already registered';
                    }
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
            throw new ValidationException($errors);
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
            $ids = array(
                'facebookID' => $user->getProperty('facebookID'),
                'googleID' => $user->getProperty('googleID'),
                'twitterID' => $user->getProperty('twitterID'),
                'spotifyID' => $user->getProperty('spotifyID'),
            );

            $return[] = array_merge($this->build($row), $ids);
        }

        return $return;
    }

    public function getUnconnectedNetworks($userId)
    {
        $tokens = $this->getAll($userId);
        $resourceOwners = $this->getResourceOwners();

        $unconnected = array();
        foreach ($resourceOwners as $resource) {
            $connected = false;
            foreach ($tokens as $token) {
                if ($token['resourceOwner'] == $resource) {
                    $connected = true;
                }
            }
            if (!$connected) {
                $unconnected[] = $resource;
            }
        }

        return $unconnected;

    }

    public function getConnectedNetworks($userId)
    {
        $tokens = $this->getAll($userId);

        $resourceOwners = array();
        foreach ($tokens as $token) {
            $resourceOwners[] = $token['resourceOwner'];
        }

        return $resourceOwners;
    }

    /**
     * For now, build just array for public fetching
     * @param SocialProfile $profile
     * @return array
     */
    public function buildFromSocialProfile(SocialProfile $profile)
    {
        return array(
            'id' => $profile->getUserId(),
            'url' => $profile->getUrl(),
            'resourceOwner' => $profile->getResource(),
        );
    }

    protected function build(Row $row)
    {
        /* @var $user Node */
        $user = $row->offsetGet('user');
        /* @var $node Node */
        $node = $row->offsetGet('token');
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

        $properties = $ordered + $properties;

        return array_merge(
            array(
                'id' => $user->getProperty('qnoow_id'),
                'username' => $user->getProperty('username'),
                'email' => $user->getProperty('email')
            ),
            $properties
        );
    }

    protected function getMetadata()
    {

        return array(
            'resourceOwner' => array('type' => 'choice', 'choices' => self::getResourceOwners(), 'required' => true),
            'oauthToken' => array('type' => 'string', 'required' => true),
            'oauthTokenSecret' => array('type' => 'string', 'required' => false),
            'createdTime' => array('type' => 'integer', 'required' => false),
            'updatedTime' => array('type' => 'integer', 'editable' => false),
            'expireTime' => array('type' => 'integer', 'required' => false),
            'refreshToken' => array('type' => 'string', 'required' => false),
            'resourceId' => array('type' => 'string', 'required' => false),
        );
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

    protected function saveTokenData(Node $userNode, Node $tokenNode, $resourceOwner, array $data)
    {

        if (isset($data['resourceId'])) {
            $userNode->setProperty($resourceOwner . 'ID', $data['resourceId']);
            $userNode->save();
        }

	    $type = Configuration::getResourceOwnerType($resourceOwner);
	    if ($type == 'oauth1' && $data['oauthToken']) {
		    $oauthToken = substr($data['oauthToken'], 0, strpos($data['oauthToken'], ':'));
		    $oauthTokenSecret = substr($data['oauthToken'], strpos($data['oauthToken'], ':') + 1, strpos($data['oauthToken'], '@') - strpos($data['oauthToken'], ':') - 1);

		    $data['oauthToken'] = $oauthToken;
		    $data['oauthTokenSecret'] = $oauthTokenSecret;
	    }

        $tokenNode->setProperty('resourceOwner', $resourceOwner);
        $tokenNode->setProperty('updatedTime', time());
        foreach ($data as $property => $value) {
            $tokenNode->setProperty($property, $value);
        }

        return $tokenNode->save();
    }

}