<?php

namespace Model\User;

use Event\UserDataEvent;
use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
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
     * @param EventDispatcher $dispatcher
     * @param Client $client
     */
    public function __construct(EventDispatcher $dispatcher, Client $client)
    {

        $this->dispatcher = $dispatcher;
        $this->client = $client;
    }

    /**
     * @param $userId
     * @param $linkId
     * @param $rate
     * @throws \Exception
     * @internal param $url
     * @return array
     */
    public function userRateLink($userId, $linkId, $rate)
    {
        if ($rate !== self::LIKE && $rate != self::DISLIKE && $rate != self::IGNORE) {
            throw new \Exception('"' . $rate . '" is not a valid rate');
        }

        $query = "
            MATCH
            (user:User), (link:Link)
            WHERE
            user.qnoow_id = {userId} AND id(link) = {linkId}
            OPTIONAL MATCH
            (user)-[rate]->(link)
            WHERE type(rate) <> {rate}
            DELETE rate
        ";

        if ($rate == self::LIKE || $rate == self::DISLIKE) {
            $query .= "
                CREATE UNIQUE
                (user)-[new_rate:" . $rate . "]->(link)
                RETURN user,id(link) as linkId, type(new_rate) as rate
            ";
        } else {
            $query .= "
                RETURN user,id(link) as linkId, 'IGNORE' as rate
            ";
        }

        $query = new Query(
            $this->client,
            $query,
            array(
                'userId'    => (integer)$userId,
                'linkId'    => (integer)$linkId,
                'rate'      => $rate,
            )
        );

        $result = $query->getResultSet();

        $event = new UserDataEvent($userId);
        $this->dispatcher->dispatch(\AppEvents::USER_DATA_CONTENT_RATED, $event);

        $response = array();
        foreach ($result as $row) {
            $response['id'] = $row['user']->getProperty('qnoow_id');
            $response['linkId'] = $row['linkId'];
            $response['rate'] = $row['rate'];
        }

        return $response;
    }

}
