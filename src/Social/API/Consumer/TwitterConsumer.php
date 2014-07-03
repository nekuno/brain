<?php

namespace Social\API\Consumer;

/**
 * Class TwitterConsumer
 * @package Social\API\Consumer
 */
class TwitterConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    /**
     * { @inheritdoc }
     */
    public function fetchLinks($userId = null)
    {

        $errors = array();

        $users  = $this->userProvider->getUsersByResource('twitter', $userId);

        $data = array();

        foreach ($users as $user) {

            if (!$user['twitterID']) {
                continue;
            }

            $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

            $userOptions = array(
                'oauth_access_token'        => $user['oauthToken'],
                'oauth_access_token_secret' => $user['oauthTokenSecret'],
            );

            $oauthData = array_merge($this->options, $userOptions);

            try {
                $data[$user['id']] = $this->fetch($url, $oauthData, true);
            } catch (\Exception $e) {
                $errors[] = $this->getError($e);
            }
        }

        $stored = array();

        try {
            $stored = $this->processData($data);
        } catch (\Exception $e) {
            $errors[] = $this->getError($e);
        }

        return array() !== $errors ? $errors : $stored;
    }

    /**
     * { @inheritdoc }
     */
    protected function parseLinks($userId, array $data = array())
    {
        $parsed = array();

        foreach ($data as $item) {
            if (empty($item['entities']) || empty($item['entities']['urls'][0])) {
                continue;
            }
            $link['url']         = $item['entities']['urls'][0]['expanded_url']
                ? $item['entities']['urls'][0]['expanded_url'] : $item['entities']['urls'][0]['url'] ;
            $link['title']       = array_key_exists('text', $item) ? $item['text'] : '';
            $link['description'] = '';
            $link['userId']      = $userId;

            $parsed[] = $link;
        }

        return $parsed;
    }

}
