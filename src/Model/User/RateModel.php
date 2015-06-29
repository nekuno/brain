<?php

namespace Model\User;

use Event\ContentRatedEvent;
use Everyman\Neo4j\Client;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
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
    const IGNORE = 'IGNORE';

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
     * @param $data array
     * @param $rate
     * @param bool $fireEvent
     * @return array
     * @throws \Exception
     */
    public function userRateLink($userId, array $data, $rate, $fireEvent = true)
    {
        if ($rate !== self::LIKE && $rate != self::DISLIKE && $rate != self::IGNORE) {
            throw new \Exception(sprintf('%s is not a valid rate', $rate));
        }

        switch ($rate) {
            case $this::LIKE :
                $result = $this->userLikeLink($userId, $data);
                break;
            default:
                return array();
        }

        if ($fireEvent) {
            $this->dispatcher->dispatch(\AppEvents::CONTENT_RATED, new ContentRatedEvent($userId));
        }

        return $result;
    }

    //TODO: Add $this->unrate for delete-like actions
    //TODO: Add $this->getrate for get-like actions

    /**
     * @param $userId
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function userLikeLink($userId, array $data = array())
    {

        if (empty($userId) || empty($data['id'])) return array('empty thing' => 'true');

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters(array(
            'userId' => (integer)$userId,
            'linkId' => (integer)$data['id'],
            'timestamp' => isset($data['timestamp']) ? $data['timestamp'] : null,
        ));

        $resource = !empty($data['resource']) ? $data['resource'] : 'nekuno';

        $qb->match('(u:User)', '(l:Link)')
            ->where('u.qnoow_id = { userId }', 'id(l) = { linkId }')
            ->merge('(u)-[r:LIKES]->(l)')
            ->set('r.' . $resource . '= COALESCE({ timestamp }, timestamp())')
            //max(x,y)=(x+y+abs(x-y))/2
            ->set('r.last_liked=( COALESCE(r.last_liked, 0) + COALESCE({ timestamp }, timestamp())
                                    + ABS(COALESCE(r.last_liked, 0) -  COALESCE({ timestamp }, timestamp()))
                                    )/2 ');

        $qb->with('u, r, l')
            ->optionalMatch('(u)-[a:AFFINITY|DISLIKES]-(l)')
            ->delete('a');

        $qb->returns('r');

        $result = $qb->getQuery()->getResultSet();

        $return = array();
        foreach ($result as $row) {
            $return[] = $this->buildLike($row);
        }

        return $return;

    }

    /**
     * @param Row $row
     * @return array
     */
    protected function buildLike($row)
    {
        /* @var $relationship Relationship */
        $relationship = $row->offsetGet('r');

        return array(
            'id' => $relationship->getId(),
            'resource' => $relationship->getProperty('resource'),
            'timestamp' => $relationship->getProperty('timestamp'),
        );
    }

}
