<?php

namespace ApiConsumer\Restful\Consumer;

/**
 * Class FacebookConsumer
 *
 * @package Social
 */
class FacebookConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    private $items = array();

    private $url = 'https://graph.facebook.com/v2.0/';

    private $pageLength = 20;

    /**
     * { @inheritdoc }
     */
    public function fetchLinks($userId = null)
    {

        $users = $this->userProvider->getUsersByResource('facebook', $userId);

        $links = array();

        foreach ($users as $user) {

            if (!$user['facebookID']) {

                continue;
            }

            $this->url .= $user['facebookID'];
            $this->url .= '/links';
            $this->url .= '?access_token=' . $user['oauthToken'];
            $this->url .= '&limit=' . $this->pageLength;

            try {
                $this->getLinksByPage();

                $links[$user['id']] = $this->formatResponse();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $links;
    }

    private function getLinksByPage($lastItemToken = null)
    {

        $url = $this->url;
        if ($lastItemToken) {
            $url .= '&after=' . $lastItemToken;
        }

        $response = $this->makeRequestJSON($url);

        $this->items = array_merge($this->items, $response['data']);

        if (array_key_exists('paging', $response)) {
            if (array_key_exists('cursors', $response['paging'])) {
                return call_user_func(array($this, __FUNCTION__), $response['paging']['cursors']['after']);
            }
        }

        return;
    }

    /**
     * { @inheritdoc }
     */
    protected function formatResponse()
    {

        $parsed = array();

        foreach ($this->items as $item) {
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
