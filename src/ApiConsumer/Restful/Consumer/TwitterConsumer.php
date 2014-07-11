<?php

namespace ApiConsumer\Restful\Consumer;

/**
 * Class TwitterConsumer
 *
 * @package Social\API\Consumer
 */
class TwitterConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    /**
     * { @inheritdoc }
     */
    public function fetchLinks($userId = null)
    {

        $users = $this->userProvider->getUsersByResource('twitter', $userId);

        $links = array();

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
                $response       = $this->makeRequestJSON($url, $oauthData, true);
                $links[$userId] = $this->formatResponse($response);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $links;
    }

    /**
     * @param array $response
     * @return array
     */
    public function formatResponse(array $response = array())
    {

        $formatted = array();

        foreach ($response as $item) {
            if (empty($item['entities']) || empty($item['entities']['urls'][0])) {
                continue;
            }

            $url = $item['entities']['urls'][0]['expanded_url']
                ? $item['entities']['urls'][0]['expanded_url']
                : $item['entities']['urls'][0]['url'];

            $link                = array();
            $link['url']         = $url;
            $link['title']       = array_key_exists('text', $item) ? $item['text'] : '';
            $link['description'] = '';

            $formatted[] = $link;
        }

        return $formatted;
    }
}
