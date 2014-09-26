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
     * @param $url
     * @param $rate
     * @throws \Exception
     * @return array
     */
    public function userRateLink($userId, $url, $rate)
    {
        if ($rate !== self::LIKE && $rate != self::DISLIKE) {
            throw new \Exception('"' . $rate . '" is not a valid rate');
        }

        $template = "
            MATCH
            (user:User), (link:Link)
            WHERE
            user.qnoow_id = {userId} AND link.url = {url}
            OPTIONAL MATCH
            (user)-[rate]->(link)
            WHERE type(rate) <> {rate}
            DELETE rate
            CREATE UNIQUE
            (user)-[new_rate:" . $rate . "]->(link)
            RETURN user,link, type(new_rate) as rate
        ";

        $query = new Query(
            $this->client,
            $template,
            array(
                'userId'    => (integer)$userId,
                'url'       => $url,
                'rate'      => $rate,
            )
        );

        $result = $query->getResultSet();

        $event = new UserDataEvent($userId);
        $this->dispatcher->dispatch(\AppEvents::USER_DATA_CONTENT_RATED, $event);

        $response = array();
        foreach ($result as $row) {
            $response['id'] = $row['user']->getProperty('qnoow_id');
            $response['url'] = $row['link']->getProperty('url');
            $response['rate'] = $row['rate'];
        }

        return $response;
    }

}
