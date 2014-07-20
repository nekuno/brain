<?php

namespace ApiConsumer\Restful\Consumer;

/**
 * Class TwitterConsumer
 *
 * @package Social\API\Consumer
 */
class TwitterConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    private $items = array();

    private $url = 'https://api.twitter.com/1.1/';

    private $pageLength = 200;

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

            $this->url .= 'statuses/user_timeline.json';
            $this->url .= '?count=' . $this->pageLength;
            $this->url .= '&trim_user=true';
            $this->url .= '&exclude_replies=true';
            $this->url .= '&contributor_details=false';
            $this->url .= '&include_rts=false';

            $oauthOptions = array(
                'legacy'                    => true,
                'oauth_access_token'        => $user['oauthToken'],
                'oauth_access_token_secret' => $user['oauthTokenSecret'],
            );

            $this->options = array_merge($this->options, $oauthOptions);

            try {
                $this->getLinksByPage();

                $links[$user['id']] = $this->formatResponse();

                return $links;
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    private function getLinksByPage($lastItemId = null)
    {

        $url = $this->url;
        if ($lastItemId) {
            $url .= '&since_id=' . $lastItemId;
        }

        $response = $this->makeRequestJSON($url);

        $this->items = array_merge($this->items, $response);

        $itemsCount = count($response);
        if ($itemsCount > 0 && $itemsCount > $this->pageLength) {
            $lastItem = $response[count($response) - 1];

            return call_user_func(array($this, __FUNCTION__), $lastItem['id_str']);
        }

        return;
    }

    /**
     * @param array $response
     * @return array
     */
    public function formatResponse()
    {

        $formatted = array();

        foreach ($this->items as $item) {
            if (empty($item['entities']) || empty($item['entities']['urls'][0])) {
                continue;
            }

            $url = $item['entities']['urls'][0]['expanded_url']
                ? $item['entities']['urls'][0]['expanded_url']
                : $item['entities']['urls'][0]['url'];

            $link                   = array();
            $link['url']            = $url;
            $link['title']          = array_key_exists('text', $item) ? $item['text'] : null;
            $link['description']    = null;
            $link['resourceItemId'] = array_key_exists('id', $item) ? $item['id'] : null;
            $link['resource']       = 'twitter';

            $formatted[] = $link;
        }

        return $formatted;
    }
}
