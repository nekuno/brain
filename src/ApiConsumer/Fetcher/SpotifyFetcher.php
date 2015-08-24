<?php

namespace ApiConsumer\Fetcher;

class SpotifyFetcher extends AbstractFetcher
{
    //max limits allowed by Spotify API to reduce calls
    const MAX_PLAYLISTS_PER_USER = 50;
    const MAX_TRACKS_PER_PLAYLIST = 100;

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
            $playlists = $this->getAllItemsFromPaginatedURL($this->url, $this->user, $this::MAX_PLAYLISTS_PER_USER);
            $allTracks = array();
            if (isset($playlists)) {
                foreach ($playlists as $playlist) {
                    if ($playlist['owner']['id'] == $user['spotifyID']) {

                        $this->url = 'users/' . $user['spotifyID'] . '/playlists/' . $playlist['id'] . '/tracks';

                        try {
                            $tracks = $this->getAllItemsFromPaginatedURL($this->url, $this->user, $this::MAX_TRACKS_PER_PLAYLIST);
                            $currentPlaylistTracks = $this->parseLinks($tracks);
                            $allTracks = array_merge($currentPlaylistTracks, $allTracks);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }

            $this->url = 'users/' . $user['spotifyID'] . '/starred/tracks';
            $starredTracks = $this->getAllItemsFromPaginatedURL($this->url, $this->user, $this::MAX_TRACKS_PER_PLAYLIST);
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

        foreach ($response as $item) {
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

    /**
     * @param $url
     * @param $user
     * @param $limit
     * @return array
     */
    protected function getAllItemsFromPaginatedURL($url, $user, $limit)
    {
        $items = array();
        while ($url) {
            $partialResponse = $this->resourceOwner->authorizedHttpRequest($url, array('limit' => $limit), $user);

            $url=null;
            if ($partialResponse['next']) {
                $startPos=strpos($partialResponse['next'],'users');
                $url=substr($partialResponse['next'],$startPos);
            }

            $items = array_merge($items, $partialResponse['items']);
        };

        return $items;
    }
}
