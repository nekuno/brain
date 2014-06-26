<?php

namespace Social\Consumer;

use Silex\Application;

/**
 * Class FacebookConsumer
 * @package Social
 */
class FacebookFeedConsumer extends GenericConsumer
{

    /**
     * Fetch data for all users if $userId is null
     *
     * @param null $userId
     * @return array
     */
    public function fetchLinks($userId = null)
    {

        $errors = array();
        $users = $this->getUsersByResource('facebook', $userId);

        $data = array();
        foreach ($users as $user) {

            $url = 'https://graph.facebook.com/v2.0/' . $user['facebookID'] . '/links'
                . '?access_token=' . $user['oauthToken'];

            try {
                $data[$user['id']] = $this->fetchDataFromUrl($url);
            } catch(\Exception $e) {
                $errors[] = $this->getError($e);

            }

        }

        $links = array();
        foreach ($data as $userId => $shared) {
            try {
                array_merge($links, $this->parseLinks($shared, $userId));;
            } catch(\Exception $e) {
                $errors[] = $this->getError($e);

            }
        }

        try {
            $stored = $this->storeLinks($links);
        } catch(\Exception $e) {
            $errors[] = $this->getError($e);
        }

        return isset($stored) ? $stored : array();

    }

    /**
     * Save links to Graph DB
     *
     * @param $data fetched data from user feed
     * @param $userId
     * @return array
     * @throws \Exception
     */
    protected function parseLinks($data, $userId)
    {
        $parsed = array();

        foreach ($data['data'] as $item) {
            $link['url']   = $item['link'];
            $link['title'] = array_key_exists('name', $item) ? $item['name'] : '';;
            $link['description'] = array_key_exists('description', $item) ? $item['description'] : '';
            $link['userId']      = $userId;

            $parsed[] = $link;
        }

        return $parsed;
    }

}