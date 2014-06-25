<?php

namespace Social\Consumer;

use Guzzle\Http\Exception\RequestException;
use Silex\Application;

/**
 * Class FacebookConsumer
 * @package Social
 */
class FacebookFeedConsumer
{

    /**
     * @var \Silex\Application
     */
    protected $app;

    public function __construct(Application $app){
        $this->app = $app;
    }

    /**
     * Fetch data for all users if $userId is null
     *
     * @param null $userId
     * @return array
     */
    public function fetch($userId = null)
    {

        $users = $this->getUsersByResource('facebook', $userId);

        $links = array();

        foreach ($users as $user) {
            $data = $this->fetchLinksFromUser($user);

            $links[$user['id']] = $this->storeLinks($data, $user['id']);
        }

        return $links;

}

    /**
     * Fetch last links from user feed on Facebook
     *
     * @param $user
     * @return mixed
     * @throws \Exception
     * @throws RequestException
     */
    protected function fetchLinksFromUser($user)
    {
        $client = $this->app['guzzle.client'];

        $url = 'https://graph.facebook.com/v2.0/' . $user['facebookID'] . '/links'
            . '?access_token=' . $user['oauthToken'];

        $request = $client->get($url);

        try {
            $response = $request->send();
            $data = $response->json();
        } catch (RequestException $e) {
            throw $e;
        }

        return $data;
    }

    /**
     * Get users by resource owner
     *
     * @param $resource
     * @param $userId
     * @return mixed
     */
    protected function getUsersByResource($resource, $userId = null)
    {

        $sql = "SELECT * " .
            " FROM users AS u" .
            " INNER JOIN user_access_tokens AS ut ON u.id = ut.user_id" .
            " WHERE ut.resourceOwner = '" . $resource . "'";

        if(null !== $userId){
            $sql .= " AND u.id = " . $userId;
        }

        $sql .= ";";

        try {
            $users = $this->app['db']->fetchAll($sql);
        } catch (\Exception $e) {
            throw new $e;
        }

        return $users;
    }

    /**
     * Save links to Graph DB
     *
     * @param $data fetched data from user feed
     * @param $userId
     * @return array
     * @throws \Exception
     */
    protected function storeLinks($data, $userId)
    {
        $result = array();

        foreach ($data['data'] as $item) {
            $link['url']   = $item['link'];
            $link['title'] = array_key_exists('name', $item) ? $item['name'] : '';;
            $link['description'] = array_key_exists('description', $item) ? $item['description'] : '';
            $link['userId']      = $userId;

            try {
                $model = $this->app['content.model'];
                $link  = $model->addLink($link);
                if ($link) {
                    $result[] = $link;
                }
            } catch (\Exception $e) {
                throw $e;
            }

        }

        return $result;
    }

}