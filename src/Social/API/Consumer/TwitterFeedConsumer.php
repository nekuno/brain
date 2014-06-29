<?php

namespace Social\API\Consumer;

/**
 * Class TwitterFeedConsumer
 * @package Social\API\Consumer
 */
class TwitterFeedConsumer extends AbstractConsumer implements LinksConsumerInterface
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

        $users  = $this->userProvider->getUsersByResource('twitter', $userId);

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
                $data[$user['id']] = $this->httpConnector->fetch($url, $this->config, true);
            } catch (\Exception $e) {
                $errors[] = $this->getError($e);
            }
        }

        try {
            $stored = $this->processData($data);
        } catch (\Exception $e) {
            $errors[] = $this->getError($e);
        }

        return isset($errors) ? $errors : $stored;
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