<?php

namespace ApiConsumer\Restful\Consumer;

/**
 * Class FacebookConsumer
 *
 * @package Social
 */
class SpotifyConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    /**
     * { @inheritdoc }
     */
    public function fetchLinks($userId = null)
    {

        $users = $this->userProvider->getUsersByResource('spotify', $userId);

        $links = array();

        foreach ($users as $user) {

            if (!$user['spotifyID']) {

                continue;
            }

            $url = 'https://api.spotify.com/v1/users/'. $user['spotifyID'] . '/playlists/';
            $headers = array('Authorization' => 'Bearer ' . $user['oauthToken']);
            $this->options['headers'] = $headers;
            try {
                $playlists = $this->makeRequestJSON($url);

                $allTracks = array();
                if (isset($playlists['items'])) {
                    foreach ($playlists['items'] as $playlist) {
                        $url = 'https://api.spotify.com/v1/users/'. $user['spotifyID'] . '/playlists/'. $playlist['id'].'/tracks';

                        try {
                            $tracks = $this->makeRequestJSON($url);
                            $currentPlaylistTracks = $this->formatResponse($tracks);
                            $allTracks = array_merge($currentPlaylistTracks, $allTracks);
                        } catch (\Exception $e) {
                            throw $e;
                        }
                    }
                }

                $links[$user['id']] = $allTracks;
            } catch (\Exception $e) {
                throw $e;
            }
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
            $id = $item['track']['id'];

            $link['url']         = $item['track']['external_urls']['spotify'];
            $link['title']       = $item['track']['name'];
            
            $artistList = array();
            foreach ($item['track']['artists'] as $artist) {
                $artistList[] = $artist['name'];
            }

            $link['description'] = $item['track']['album']['name'] . ' : ' .implode(', ', $artistList);

            $parsed[$id] = $link;
        }

        return $parsed;
    }
}
