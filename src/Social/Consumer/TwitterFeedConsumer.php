<?php

namespace Social\Consumer;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class TwitterFeedConsumer extends GenericConsumer implements LinksConsumerInterface
{

    private $config = array(
        'oauth_consumer_key'    => 'm9NY9ZghlVeYFVk8fbPp8pp1g',
        'oauth_consumer_secret' => 'yQ5XBabTj1vzCCBVeJoqX9Bli4zcjVWsF6FxHvPtZwkAJseu1l'
    );

    /**
     * { @inheritdoc }
     */
    public function fetchLinks($userId = null)
    {
        $errors = array();
        $users  = $this->getUsersByResource('twitter', $userId);

        $data = array();
        foreach ($users as $user) {

            if (!$user['twitterID']) continue;

            $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

            $userOauthTokens = array(
                'oauth_access_token'        => $user['oauthToken'],
                'oauth_access_token_secret' => $user['oauthTokenSecret'],
            );

            $this->config = array_merge($this->config, $userOauthTokens);

            try {
                $data[$user['id']] = $this->fetchDataFromUrl($url);
            } catch (\Exception $e) {
                $errors[] = $this->getError($e);
            }
        }

        try {
            $stored = $this->processData($data);
        } catch (\Exception $e) {
            $errors[] = $this->getError($e);
        }

        return isset($stored) ? $stored : array();
    }

    /**
     * { @inheritdoc }
     */
    protected function fetchDataFromUrl($url)
    {
        $client = $this->app['guzzle.client'];

        $oauth = new Oauth1([
            'consumer_key'    => $this->config['oauth_consumer_key'],
            'consumer_secret' => $this->config['oauth_consumer_secret'],
            'token'           => $this->config['oauth_access_token'],
            'token_secret'    => $this->config['oauth_access_token_secret']
        ]);

        $client->getEmitter()->attach($oauth);

        $response = $client->get($url, array('auth' => 'oauth'));

        try {
            $data     = $response->json();
        } catch (RequestException $e) {
            throw $e;
        }

        return $data;
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

        foreach ($data as $item) {
            if (empty($item['entities']) || empty($item['entities']['urls'][0])) {
                continue;
            }
            $link['url']         = $item['entities']['urls'][0]['expanded_url'] ? $item['entities']['urls'][0]['expanded_url'] : $item['entities']['urls'][0]['url'] ;
            $link['title']       = array_key_exists('text', $item) ? $item['text'] : '';
            $link['description'] = '';
            $link['userId']      = $userId;

            $parsed[] = $link;
        }

        return $parsed;
    }

}