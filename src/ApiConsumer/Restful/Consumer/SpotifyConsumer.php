<?php

namespace ApiConsumer\Restful\Consumer;

/**
 * Class SpotifyConsumer
 *
 * @package Social
 */
class SpotifyConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    /**
     * { @inheritdoc }
     */
    public function fetchLinksFromUserFeed($userId = null)
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
                        if ($playlist['owner']['id'] == $user['spotifyID'] ) {
                            $url = $playlist['href'] . '/tracks';

                            try {
                                $tracks = $this->makeRequestJSON($url);
                                $currentPlaylistTracks = $this->formatResponse($tracks);
                                $allTracks = array_merge($currentPlaylistTracks, $allTracks);
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }
                }

                $url = 'https://api.spotify.com/v1/users/'. $user['spotifyID'] . '/starred/tracks';
                $starredTracks = $this->makeRequestJSON($url);
                $starredPlaylistTracks = $this->formatResponse($starredTracks);
                $allTracks = array_merge($starredPlaylistTracks, $allTracks);

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
            if (null !== $item['track']['id']) {
                $link['url']         = $item['track']['external_urls']['spotify'];
                $link['title']       = $item['track']['name'];

                $artistList = array();
                foreach ($item['track']['artists'] as $artist) {
                    $artistList[] = $artist['name'];
                }

                $link['description'] = $item['track']['album']['name'] . ' : ' .implode(', ', $artistList);
                $link['resourceItemId'] =  $item['track']['id'];
                $link['resource']       = 'spotify';

                $parsed[] = $link;
            }
        }

        return $parsed;
    }
}
