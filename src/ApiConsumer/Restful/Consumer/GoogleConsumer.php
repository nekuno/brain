<?php

namespace ApiConsumer\Restful\Consumer;

use ApiConsumer\Auth\ResourceOwnerNotConnectedException;

/**
 * Class GoogleConsumer
 *
 * @package ApiConsumer\Restful\Consumer
 */
class GoogleConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    /**
     * @var string
     */
    private $url = 'https://www.googleapis.com/plus/v1/people/';

    /**
     * @var int
     */
    private $pageLength = 20;

    /**
     * Refresh Access Token
     *
     * @param $user
     * @return string newAccessToken
     */
    private function refreshAcessToken($user)
    {
        $url = 'https://accounts.google.com/o/oauth2/token';
        $parameters = array(
            'refresh_token' => $user['refreshToken'],
            'grant_type' => 'refresh_token',
            'client_id' => $this->options['consumer_key'],
            'client_secret' => $this->options['consumer_secret'],
        );

        $response = $this->httpClient->post($url, array('body' => $parameters));
        $data = $response->json();

        $userId  = $user['id'];
        $accessToken = $data['access_token'];
        $creationTime = time();
        $expirationTime = time() + $data['expires_in'];
        $this->userProvider->updateAccessToken('google', $userId, $accessToken, $creationTime, $expirationTime);

        return $accessToken;
    }

    /**
     * { @inheritdoc }
     */
    public function fetchLinksFromUserFeed($userId)
    {

        $user = $this->userProvider->getUsersByResource('google', $userId);

        if (!$user['googleID']) {
            throw new ResourceOwnerNotConnectedException;
        }

        if ($user['expireTime'] <= time()) {
            $user['oauthToken'] = $this->refreshAcessToken($user);
        }

        $this->url .= $user['googleID'];
        $this->url .= '/activities/public';
        $this->url .= '?access_token=' . $user['oauthToken'];
        $this->url .= '&maxResults=' . $this->pageLength;
        $this->url .= '&fields=items(object(attachments(content,displayName,id,objectType,url)),title),nextPageToken';

        try {

            $rawFeed = $this->getLinksByPage();

            $links = $this->parseLinks($rawFeed);

        } catch (\Exception $e) {
            throw $e;
        }

        return $links;
    }

    /**
     * @param null $nextPageToken
     * @return mixed
     */
    private function getLinksByPage($nextPageToken = null)
    {

        $url = $this->url;
        if ($nextPageToken) {
            $url .= '&pageToken=' . $nextPageToken;
        }

        $response = $this->makeRequestJSON($url);

        $this->rawFeed = array_merge($this->rawFeed, $response['items']);

        if (array_key_exists('nextPageToken', $response)) {
            return call_user_func(array($this, __FUNCTION__), $response['nextPageToken']);
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
