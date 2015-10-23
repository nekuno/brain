<?php

namespace ApiConsumer\Fetcher;

class SpotifyFetcher extends BasicPaginationFetcher
{
    //max limits allowed by Spotify API to reduce calls
    const MAX_PLAYLISTS_PER_USER = 50;
    const MAX_TRACKS_PER_PLAYLIST = 100;

    /**
     * @var array
     */
    protected $rawFeed = array();

    /**
     * @var array
     */
    protected $query = array();

    protected $paginationField = 'offset';

    /**
     * { @inheritdoc }
     */
    public function fetchLinksFromUserFeed($user, $public)
    {
        $this->user = $user;
        $this->rawFeed = array();

        $this->url .= 'users/' . $user['spotifyID'] . '/playlists/';

        try {
            $this->setQuery(array('limit' => $this::MAX_PLAYLISTS_PER_USER));
            $playlists = $this->getLinksByPage($public);
            $this->rawFeed = array();

            if (isset($playlists)) {
                foreach ($playlists as $playlist) {
                    if ($playlist['owner']['id'] == $user['spotifyID']) {

                        $this->url = 'users/' . $user['spotifyID'] . '/playlists/' . $playlist['id'] . '/tracks';

                        try {
                            $this->setQuery(array('limit' => $this::MAX_TRACKS_PER_PLAYLIST));
                            $this->getLinksByPage($public);

                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }

            $this->url = 'users/' . $user['spotifyID'] . '/starred/tracks';
            $this->setQuery(array('limit' => $this::MAX_TRACKS_PER_PLAYLIST));
            $this->getLinksByPage($public);

            $parsed = $this->parseLinks($this->rawFeed);

            $links = array();
            foreach ($parsed as $parsedLink){
                $links[$parsedLink['url']] = $parsedLink;
            }

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
            if (isset($item['track']) && null !== $item['track']['id']) {
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
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param array $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    protected function getItemsFromResponse($response)
    {
        return $response['items'] ?: array();
    }

    protected function getPaginationIdFromResponse($response)
    {
        if ($response['next']) {
            $startPos = strpos($response['next'], 'offset=') + 7;
            $endPos = strpos($response['next'], '&');
            $length = $endPos - $startPos;
            return substr($response['next'], $startPos, $length);
        } else {
            return null;
        }
    }
}
