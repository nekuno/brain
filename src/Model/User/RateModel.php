<?php

namespace Model\User;

use Event\ContentRatedEvent;
use Everyman\Neo4j\Client;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class RateModel
 *
 * @package Model\User
 */
class RateModel
{

    const LIKE = 'LIKES';
    const DISLIKE = 'DISLIKES';
    const UNRATE = 'UNRATES';
    const IGNORE = 'IGNORES';

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;
    /**
     * @var \Model\Neo4j\GraphManager
     */
    protected $gm;

    /**
     * @param EventDispatcher $dispatcher
     * @param Client $client
     * @param GraphManager $gm
     */
    public function __construct(EventDispatcher $dispatcher, Client $client, GraphManager $gm)
    {

        $this->dispatcher = $dispatcher;
        $this->client = $client;
        $this->gm = $gm;
    }

    /**
     * For post-like actions
     * @param $userId
     * @param $linkId
     * @param string $resource
     * @param $timestamp
     * @param string $rate
     * @param bool $fireEvent
     * @param string $originContext
     * @param string $originName
     * @return array
     * //TODO: Refactor to accept Rate object
     */
    public function userRateLink($userId, $linkId, $resource = 'nekuno', $timestamp = null, $rate = self::LIKE, $fireEvent = true, $originContext = null, $originName = null)
    {
        $this->validate($rate);

        switch ($rate) {
            case self::LIKE:
                $result = $this->userLikeLink($userId, $linkId, $resource, $timestamp, $originContext, $originName);
                break;
            case self::DISLIKE:
                $result = $this->userDislikeLink($userId, $linkId, $timestamp, $originContext, $originName);
                break;
            case self::UNRATE:
                $result = $this->userUnRateLink($userId, $linkId);
                break;
            case self::IGNORE:
                $result = $this->userIgnoreLink($userId, $linkId, $timestamp, $originContext, $originName);
                break;
            default:
                return array();
        }

        if ($fireEvent) {
            $this->dispatcher->dispatch(\AppEvents::CONTENT_RATED, new ContentRatedEvent($userId));
        }

        return $result;
    }

    /**
     * @param $userId
     * @param $rate
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function getRatesByUser($userId, $rate, $limit = 999999)
    {
        $this->validate($rate);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(u:User {qnoow_id: { userId }})')
            ->match("(u)-[r:$rate]->(l:Link)")
            ->returns('r', 'l.url as linkUrl')
            ->limit('{limit}');

        $qb->setParameters(array(
            'userId' => (integer)$userId,
            'limit' => (integer) $limit,
        ));

        $rs = $qb->getQuery()->getResultSet();

        $rates = array();
        foreach ($rs as $row)
        {
            $rates[] = $this->buildLike($row);
        }

        return $rates;
    }

    /**
     * Meant to work only on empty likes as is.
     * @param $likeId
     * @return array|bool
     * @throws \Model\Neo4j\Neo4jException
     */
    public function completeLikeById($likeId){

        $rate = self::LIKE;
        $qb = $this->gm->createQueryBuilder();
        $qb->match("(u:User)-[r:$rate]->(l:Link)")
            ->where('id(r)={likeId}')
            ->set('r.nekuno = timestamp()', 'r.updatedAt = timestamp()')
            ->returns('r', 'l.url AS linkUrl');
        $qb->setParameters(array(
            'likeId' => (integer)$likeId
        ));

        $rs = $qb->getQuery()->getResultSet();

        if ($rs->count() == 0){
            return false;
        }

        return $this->buildLike($rs->current());
    }

    /**
     * @param $userId
     * @param $linkId
     * @param string $resource
     * @param null $timestamp
     * @param null $originContext
     * @param null $originName
     * @return array
     */
    protected function userLikeLink($userId, $linkId, $resource = 'nekuno', $timestamp, $originContext, $originName)
    {

        if (empty($userId) || empty($linkId)) return array('empty thing' => 'true'); //TODO: Fix this return

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters(array(
            'userId' => (integer)$userId,
            'linkId' => (integer)$linkId,
            'timestamp' => $timestamp,
            'originContext' => $originContext,
            'originName' => $originName,
        ));

        $qb->match('(u:User {qnoow_id: { userId }})', '(l:Link)')
            ->where('id(l) = { linkId }')
            ->merge('(u)-[r:' . self::LIKE . ']->(l)')
            ->set('r.' . $resource . '= COALESCE({ timestamp }, timestamp())')
            //max(x,y)=(x+y+abs(x-y))/2
            ->set('r.updatedAt = ( COALESCE(r.updatedAt, 0) + COALESCE({ timestamp }, timestamp())
                                    + ABS(COALESCE(r.updatedAt, 0) -  COALESCE({ timestamp }, timestamp()))
                                    )/2 ');
        if ($originContext) {
            $qb->set('r.originContext = { originContext }')
                ->set('r.originName = { originName }');
        }

        $qb->with('u, r, l')
            ->optionalMatch('(u)-[a:AFFINITY|' . self::DISLIKE . '|' . self::IGNORE . ']-(l)')
            ->delete('a');

        $qb->returns('r', 'l.url as linkUrl');

        $result = $qb->getQuery()->getResultSet();

        $return = array();
        foreach ($result as $row) {
            $return[] = $this->buildLike($row);
        }

        return $return;
    }

    private function userDislikeLink($userId, $linkId, $timestamp, $originContext, $originName)
    {
        if (empty($userId) || empty($linkId)) return array('empty thing' => 'true'); //TODO: Fix this return

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters(array(
            'userId' => (integer)$userId,
            'linkId' => (integer)$linkId,
            'timestamp' => $timestamp,
            'originContext' => $originContext,
            'originName' => $originName,
        ));

        $qb->match('(u:User {qnoow_id: { userId }})', '(l:Link)')
            ->where('id(l) = { linkId }')
            ->merge('(u)-[r:' . self::DISLIKE . ']->(l)')
            ->set('r.updatedAt={timestamp}');

        if ($originContext) {
            $qb->set('r.originContext = { originContext }')
                ->set('r.originName = { originName }');
        }

        $qb->with('u, r, l')
            ->optionalMatch('(u)-[a:AFFINITY|' . self::LIKE . '|' . self::IGNORE . ']-(l)')
            ->delete('a');

        $qb->returns('r', 'l.url as linkUrl');

        $result = $qb->getQuery()->getResultSet();

        $return = array();
        foreach ($result as $row) {
            $return[] = $this->buildLike($row);
        }

        return $return;
    }

    private function userUnRateLink($userId, $linkId)
    {
        if (empty($userId) || empty($linkId)) return array('empty thing' => 'true'); //TODO: Fix this return

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters(array(
            'userId' => (integer)$userId,
            'linkId' => (integer)$linkId,
        ));

        $qb->match('(u:User {qnoow_id: { userId }})-[r:' . self::LIKE . '|' . self::DISLIKE . ']-(l:Link)')
            ->where('id(l) = { linkId }')
            ->delete('r');

        $qb->with('u, l')
            ->optionalMatch('(u)-[a:AFFINITY]-(l)')
            ->delete('a');

        $qb->getQuery()->getResultSet();

        return array();
    }

    private function userIgnoreLink($userId, $linkId, $timestamp, $originContext, $originName)
    {
        if (empty($userId) || empty($linkId)) return array('empty thing' => 'true'); //TODO: Fix this return

        $return = array();
        if (!$this->likesOrDislikes($userId, $linkId)) {
            $qb = $this->gm->createQueryBuilder();

            $qb->setParameters(array(
                'userId' => (integer)$userId,
                'linkId' => (integer)$linkId,
                'timestamp' => $timestamp,
                'originContext' => $originContext,
                'originName' => $originName,
            ));

            $qb->match('(u:User {qnoow_id: { userId }})', '(l:Link)')
                ->where('id(l) = { linkId }')
                ->merge('(u)-[r:' . self::IGNORE . ']->(l)')
                ->set('r.updatedAt=( COALESCE(r.updatedAt, 0) + COALESCE({ timestamp }, timestamp())
                                    + ABS(COALESCE(r.updatedAt, 0) -  COALESCE({ timestamp }, timestamp()))
                                    )/2 ');

            if ($originContext) {
                $qb->set('r.originContext = { originContext }')
                    ->set('r.originName = { originName }');
            }

            $qb->returns('r', 'l.url as linkUrl');

            $result = $qb->getQuery()->getResultSet();

            foreach ($result as $row) {
                $return[] = $this->buildLike($row);
            }
        }
        return $return;
    }

    /**
     * Intended to mimic a Like object
     * @param Row $row with r as Like relationship
     * @return array
     */
    protected function buildLike($row)
    {
        /* @var $relationship Relationship */
        $relationship = $row->offsetGet('r');

        $resources = array();
        $resourceOwners = array_merge(array('nekuno'), TokensModel::getResourceOwners());
        foreach ($resourceOwners as $resourceOwner){
            if ($relationship->getProperty($resourceOwner)) {
                $resources[$resourceOwner] = $relationship->getProperty($resourceOwner);
            }
        }

        return array(
            'id' => $relationship->getId(),
            'resources' => $resources,
            'timestamp' => $relationship->getProperty('updatedAt'),
            'linkUrl' => $row->offsetGet('linkUrl'),
            'originContext' => $relationship->getProperty('originContext'),
            'originName' => $relationship->getProperty('originName'),
        );
    }

    /**
     * @param $rate
     * @throws \Exception
     */
    private function validate($rate)
    {
        $errors = array();
        if ($rate !== self::LIKE && $rate != self::DISLIKE && $rate !== self::UNRATE && $rate != self::IGNORE) {
            $errors['rate'] = array(sprintf('%s is not a valid rate', $rate));
        }

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    private function likesOrDislikes($userId, $linkId)
    {
        $qb = $this->gm->createQueryBuilder()
            ->match("(u:User {qnoow_id: { userId }})-[r:" . self::LIKE . '|' . self::DISLIKE . "]->(l:Link)")
            ->where("id(l) = { linkId }")
            ->setParameters(array(
                'userId' => (integer)$userId,
                'linkId' => (integer)$linkId,
            ))
            ->returns('COUNT(r) AS relCount');

        $result = $qb->getQuery()->getResultSet();
        /* @var $row Row */
        $row = $result->current();
        $count = $row->offsetGet('relCount');

        return $count > 0;
    }

}
