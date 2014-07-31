<?php

namespace ApiConsumer\Restful\Consumer;

use ApiConsumer\Auth\ResourceOwnerNotConnectedException;

/**
 * Class FacebookConsumer
 *
 * @package Social
 */
class FacebookConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    /**
     * @var string API base url
     */
    private $baseUrl = 'https://graph.facebook.com/v2.0/';

    /**
     * @var string current user feed url
     */
    private $url;

    /**
     * @var int
     */
    private $pageLength = 20;

    /**
     * { @inheritdoc }
     */
    public function fetchLinksFromUserFeed($userId = null)
    {

        $user = $this->userProvider->getUsersByResource('facebook', $userId);

        if (!$user['facebookID']) {
            throw new ResourceOwnerNotConnectedException;
        }

        $this->url = $this->baseUrl;
        $this->url .= $user['facebookID'];
        $this->url .= '/links';
        $this->url .= '?access_token=' . $user['oauthToken'];
        $this->url .= '&limit=' . $this->pageLength;

        try {
            $rawFeed = $this->fetchFeed();

            $links = $this->parseLinks($rawFeed);

        } catch (\Exception $e) {
            throw $e;
        }

        return $links;
    }

    private function fetchFeed($lastItemToken = null)
    {

        $url = $this->url;
        if ($lastItemToken) {
            $url .= '&after=' . $lastItemToken;
        }

        $response = $this->makeRequestJSON($url);

        $this->rawFeed = array_merge($this->rawFeed, $response['data']);

        if (array_key_exists('paging', $response)) {
            if (array_key_exists('cursors', $response['paging'])) {
                return call_user_func(array($this, __FUNCTION__), $response['paging']['cursors']['after']);
            }
        }

        return $this->rawFeed;
    }

    /**
     * { @inheritdoc }
     */
    protected function parseLinks(array $rawFeed)
    {

        $parsed = array();

        foreach ($rawFeed as $item) {
            $link['url']            = $item['link'];
            $link['title']          = array_key_exists('name', $item) ? $item['name'] : null;
            $link['description']    = array_key_exists('description', $item) ? $item['description'] : null;
            $link['resourceItemId'] = array_key_exists('id', $item) ? (int)$item['id'] : null;
            $link['resource']       = 'facebook';

            $parsed[] = $link;
        }

        return $parsed;
    }
}
