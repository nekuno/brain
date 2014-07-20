<?php

namespace ApiConsumer\Restful\Consumer;

/**
 * Class GoogleConsumer
 *
 * @package Social\API\Consumer
 */
class GoogleConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    private $items = array();

    private $url = 'https://www.googleapis.com/plus/v1/people/';

    private $pageLength = 20;

    /**
     * { @inheritdoc }
     */
    public function fetchLinks($userId = null)
    {

        $users = $this->userProvider->getUsersByResource('google', $userId);

        foreach ($users as $user) {

            if (!$user['googleID']) {
                continue;
            }

            $this->url .= $user['googleID'];
            $this->url .= '/activities/public';
            $this->url .= '?access_token=' . $user['oauthToken'];
            $this->url .= '&maxResults=' . $this->pageLength;
            $this->url .= '&fields=items(object(attachments(content,displayName,id,objectType,url)),title),nextPageToken';

            try {

                $this->getLinksByPage();

                $links[$user['id']] = $this->formatResponse();

                return $links;
            } catch (\Exception $e) {
                throw $e;
            }
        }

    }

    private function getLinksByPage($nextPageToken = null)
    {

        $url = $this->url;
        if ($nextPageToken) {
            $url .= '&pageToken=' . $nextPageToken;
        }

        $response = $this->makeRequestJSON($url);

        $this->items = array_merge($this->items, $response['items']);

        if (array_key_exists('nextPageToken', $response)) {
            return call_user_func(array($this, __FUNCTION__), $response['nextPageToken']);
        }

        return;
    }

    protected function formatResponse()
    {

        $parsed = array();

        foreach ($this->items as $item) {
            if (!array_key_exists('object', $item) || !array_key_exists('attachments', $item['object'])) {
                continue;
            }

            $item = $item['object']['attachments'][0];

            $link['url']            = $item['url'];
            $link['title']          = array_key_exists('displayName', $item) ? $item['displayName'] : null;
            $link['description']    = array_key_exists('content', $item) ? $item['content'] : null;
            $link['resourceItemId'] = array_key_exists('id', $item) ? $item['id'] : null;
            $link['resource']       = 'google';

            $parsed[] = $link;
        }

        return $parsed;
    }
}
