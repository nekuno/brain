<?php

namespace Social\API\Consumer;

use Silex\Application;

/**
 * Class FacebookConsumer
 * @package Social
 */
class FacebookFeedConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    /**
     * { @inheritdoc }
     */
    public function fetchLinks($userId = null)
    {

        $errors = array();
        $users = $this->userProvider->getUsersByResource('facebook', $userId);

        $data = array();
        foreach ($users as $user) {

            if(!$user['facebookID']) continue;

            $url = 'https://graph.facebook.com/v2.0/' . $user['facebookID'] . '/links'
                . '?access_token=' . $user['oauthToken'];

            try {
                $data[$user['id']] = $this->httpConnector->fetch($url);
            } catch(\Exception $e) {
                $errors[] = $this->getError($e);

            }

        }

        try {
            $stored = $this->processData($data);
        } catch(\Exception $e) {
            $errors[] = $this->getError($e);
        }

        return isset($stored) ? $stored : array();

    }

    /**
     * Parse links to model expected format
     *
     * @param $data
     * @param $userId
     * @return mixed
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