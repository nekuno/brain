<?php

namespace ApiConsumer\Restful\Consumer;

use ApiConsumer\Auth\ResourceOwnerNotConnectedException;

/**
 * Class SpotifyConsumer
 *
 * @package Social
 */
class SpotifyConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    private $baseUrl = 'https://api.spotify.com/v1';

    private $url = null;

    /**
     * Refresh Access Token
     *
     * @param $user
     * @return string newAccessToken
     */
    private function refreshAcessToken($user)
    {
        $url = "https://accounts.spotify.com/api/token?grant_type=refresh_token&refresh_token=" . $user['refreshToken'];
        $authorization = base64_encode($this->options['consumer_key'] . ":" . $this->options['consumer_secret']);
        $headers = array('Authorization' => 'Basic ' . $authorization);

        $response = $this->httpClient->post($url, array('headers' => $headers));
        $data = $response->json();

        $userId  = $user['id'];
        $accessToken = $data['access_token'];
        $creationTime = time();
        $expirationTime = time() + $data['expires_in'];
        $this->userProvider->updateAccessToken('spotify', $userId, $accessToken, $creationTime, $expirationTime);

        return $accessToken;
    }

    /**
     * { @inheritdoc }
     */
    public function fetchLinksFromUserFeed($userId)
    {

        $user = $this->userProvider->getUsersByResource('spotify', $userId);

        if (!$user['spotifyID']) {
            throw new ResourceOwnerNotConnectedException;
        }

        if ($user['expireTime'] <= time()) {
            $user['oauthToken'] = $this->refreshAcessToken($user);
        }

        $this->url = $this->baseUrl;
        $this->url .= '/users/' . $user['spotifyID'];
        $this->url .= '/playlists/';

        $headers                  = array('Authorization' => 'Bearer ' . $user['oauthToken']);
        $this->options['headers'] = $headers;
        try {
            $playlists = $this->makeRequestJSON($this->url);

            $allTracks = array();
            if (isset($playlists['items'])) {
                foreach ($playlists['items'] as $playlist) {
                    if ($playlist['owner']['id'] == $user['spotifyID']) {
                        $this->url = $playlist['href'] . '/tracks';

                        try {
                            $tracks                = $this->makeRequestJSON($this->url);
                            $currentPlaylistTracks = $this->formatResponse($tracks);
                            $allTracks             = array_merge($currentPlaylistTracks, $allTracks);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }

            $this->url             = 'https://api.spotify.com/v1/users/' . $user['spotifyID'] . '/starred/tracks';
            $starredTracks         = $this->makeRequestJSON($this->url);
            $starredPlaylistTracks = $this->formatResponse($starredTracks);
            $allTracks             = array_merge($starredPlaylistTracks, $allTracks);

            $links = $allTracks;
        } catch (\Exception $e) {
            throw $e;
        }

        return $links;
    }

    /**
     * { @inheritdoc }
     */
    protected function formatResponse(array $response = array())
    {

        $parsed = array();

        foreach ($response['items'] as $item) {
            if (null !== $item['track']['id']) {
                $link['url']   = $item['track']['external_urls']['spotify'];
                $link['title'] = $item['track']['name'];

                $artistList = array();
                foreach ($item['track']['artists'] as $artist) {
                    $artistList[] = $artist['name'];
                }

                $link['description']    = $item['track']['album']['name'] . ' : ' . implode(', ', $artistList);
                $link['resourceItemId'] = $item['track']['id'];
                $link['resource']       = 'spotify';

                $parsed[] = $link;
            }
        }

        return $parsed;
    }
}
