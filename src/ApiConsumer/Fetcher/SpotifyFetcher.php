<?php

namespace ApiConsumer\Fetcher;

class SpotifyFetcher extends AbstractFetcher
{
    /**
     * @var array
     */
    protected $rawFeed = array();

    /**
     * { @inheritdoc }
     */
    public function fetchLinksFromUserFeed($user)
    {
        $this->user = $user;
        $this->rawFeed = array();

        $this->url .= 'users/' . $user['spotifyID'] . '/playlists/';

        try {
            $playlists = $this->resourceOwner->authorizedHttpRequest($this->url, array(), $this->user);

            $allTracks = array();
            if (isset($playlists['items'])) {
                foreach ($playlists['items'] as $playlist) {
                    if ($playlist['owner']['id'] == $user['spotifyID']) {

                        $this->url = 'users/' . $user['spotifyID'] . '/playlists/' . $playlist['id'] . '/tracks';

                        try {
                            $tracks = $this->resourceOwner->authorizedHttpRequest($this->url, array(), $this->user);
                            $currentPlaylistTracks = $this->parseLinks($tracks);
                            $allTracks = array_merge($currentPlaylistTracks, $allTracks);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }

            $this->url = 'users/' . $user['spotifyID'] . '/starred/tracks';
            $starredTracks = $this->resourceOwner->authorizedHttpRequest($this->url, array(), $this->user);
            $starredPlaylistTracks = $this->parseLinks($starredTracks);
            $allTracks = array_merge($starredPlaylistTracks, $allTracks);

            $links = $allTracks;
        } catch (\Exception $e) {
            throw $e;
        }

        return $links;
    }

    /**
     * { @inheritdoc }
     */
    protected function parseLinks(array $response = array())
    {

        $parsed = array();

        foreach ($response['items'] as $item) {
            if (null !== $item['track']['id']) {
                $link['url'] = $item['track']['external_urls']['spotify'];
                $link['title'] = $item['track']['name'];

                $artistList = array();
                foreach ($item['track']['artists'] as $artist) {
                    $artistList[] = $artist['name'];
                }

                $timestamp = null;
                if (array_key_exists('added_at', $item)) {
                    $date = new \DateTime($item['added_at']);
                    $timestamp = ($date->getTimestamp()) * 1000;
                }

                $link['description'] = $item['track']['album']['name'] . ' : ' . implode(', ', $artistList);
                $link['resourceItemId'] = $item['track']['id'];
                $link['timestamp'] = $timestamp;

                $link['resource'] = 'spotify';

                $parsed[] = $link;
            }
        }

        return $parsed;
    }
}
