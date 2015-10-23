<?php

namespace ApiConsumer\Fetcher;

class YoutubeFetcher extends BasicPaginationFetcher
{
    protected $paginationField = 'pageToken';

    protected $pageLength = 20;

    protected $paginationId = null;

    protected $query = array();

    static public $PLAYLISTS_TO_EXCLUDE = array('watchHistory', 'watchLater');

    public function getUrl()
    {
        return $this->url;

    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    protected function getQuery()
    {
        return $this->query;
    }

    protected function setQuery(array $query)
    {
        $this->query = $query;
    }

    protected function getItemsFromResponse($response)
    {
        $items = array();
        if (isset($response['items'])) {
            foreach ($response['items'] as $item) {
                if ($item['kind'] == 'youtube#playlistItem'
                    || $item['kind'] == 'youtube#activity'
                ) {
                    $items[$this->generateYoutubeUrl($item)] = $item;
                } else {
                    $items[] = $item;
                }
            }
        }
        return $items;
    }

    protected function getPaginationIdFromResponse($response)
    {
        $paginationId = null;

        if (isset($response['nextPageToken'])) {
            $paginationId = $response['nextPageToken'];
        }

        if ($this->paginationId === $paginationId) {
            return null;
        }

        $this->paginationId = $paginationId;

        return $paginationId;
    }

    protected function getYoutubeUrlFromResourceId($resourceId)
    {
        $url = "";
        if (isset($resourceId['kind'])) {
            switch ($resourceId['kind']) {

                case 'youtube#video':
                    if (isset($resourceId['videoId'])) {
                        $url = 'https://www.youtube.com/watch?v=' . $resourceId['videoId'];
                    }
                    break;

                case 'youtube#channel':
                    if (isset($resourceId['channelId'])) {
                        $url = 'https://www.youtube.com/channel/' . $resourceId['channelId'];
                    }
                    break;

                case 'youtube#playlist':
                    if (isset($resourceId['playlistId'])) {
                        $url = 'https://www.youtube.com/playlist?list=' . $resourceId['playlistId'];
                    }
                    break;
            }
        }

        return $url;
    }

    protected function generateYoutubeUrl($item)
    {
        $url = "";
        if (!isset($item['snippet']['type'])) {
            $url = $this->getYoutubeUrlFromResourceId($item['snippet']['resourceId']);
        } else {
            switch ($item['snippet']['type']) {
                case 'upload':
                    if (isset($item['contentDetails']['upload']['videoId'])) {
                        $url = $this->getYoutubeUrlFromResourceId(
                            array(
                                'kind' => 'youtube#video',
                                'videoId' => $item['contentDetails']['upload']['videoId']
                            )
                        );
                    }
                    break;

                case 'like':
                case 'favorite':
                case 'subscription':
                case 'playlistItem':
                case 'recommendation':
                case 'social':
                    $activity = $item['snippet']['type'];
                    if (isset($item['contentDetails'][$activity]['resourceId'])) {
                        $url = $this->getYoutubeUrlFromResourceId($item['contentDetails'][$activity]['resourceId']);
                    }
                    break;

                case 'bulletin':
                case 'channelItem':
                default:
                    break;
            }
        }

        return $url;
    }

    public function fetchLinksFromUserFeed($user, $public)
    {
        $this->user = $user;
        $this->rawFeed = array();

        if ($this->user['network'] !== 'youtube')
        {
            return array();
        }

        $channels = $this->getChannelsFromUser($public);
        $links = $this->getVideosFromChannels($channels, $public);

        $links = array_merge($links, $this->rawFeed);
        return $this->parseLinks($links);
    }

    private function getChannelsFromUser($public){



        $this->setUrl('youtube/v3/channels');
        $query = array(
            'maxResults' => $this->pageLength,
            'part' => 'contentDetails'
        );
        if (!$public) {
            $query['mine'] = 'true';
        } else {
            $query['id'] = $this->resourceOwner->getUsername($this->user);
        }

        $this->setQuery($query);
        $channels = $this->getLinksByPage($public);
        return $channels;
    }

    /**
     * @param array $channels
     * @param bool $public
     * @return array
     */
    private function getVideosFromChannels(array $channels, $public = false)
    {

        $links = array();
        foreach ($channels as $channel) {

            $this->rawFeed = array();

            $this->setUrl('youtube/v3/playlists');
            $this->setQuery(array(
                'maxResults' => $this->pageLength,
                'channelId' => $channel['id'],
                'part' => 'snippet,contentDetails'
            ));
            try {
                $this->getLinksByPage($public);
                $playlists = array();
                foreach ($this->rawFeed as $p) {
                    $playlists[$p['snippet']['title']] = $p['id'];
                }
            } catch (\Exception $e) {
                continue;
            }

            $playlists = array_merge($playlists,
                $channel['contentDetails']['relatedPlaylists']);

            $this->rawFeed = array();
            $this->setUrl('youtube/v3/playlistItems');
            foreach ($playlists as $key => $playlist) {

                if (in_array($key, $this::$PLAYLISTS_TO_EXCLUDE)) {
                    continue;
                }
                $this->setQuery(array(
                    'maxResults' => $this->pageLength,
                    'playlistId' => $playlist,
                    'part' => 'snippet,contentDetails'
                ));
                try {
                    $this->getLinksByPage($public);
                } catch (\Exception $exception) {
                    //Some default lists return 404 if empty.
                }

            }
            $links = array_merge($links, $this->rawFeed);

        }

        return $links;
    }

    /**
     * @param array $rawFeed
     * @return array
     */
    protected function parseLinks(array $rawFeed)
    {
        $parsed = array();

        foreach ($rawFeed as $item) {
            if (!array_key_exists('snippet', $item) || !array_key_exists('contentDetails', $item)) {
                continue;
            }

            $url = $this->generateYoutubeUrl($item);
            if (!$url) {
                continue;
            }

            $timestamp = null;
            if (array_key_exists('publishedAt', $item['snippet'])) {
                $date = new \DateTime($item['snippet']['publishedAt']);
                $timestamp = ($date->getTimestamp()) * 1000;
            }

            $link['url'] = $url;
            $link['title'] = array_key_exists('title', $item['snippet']) ? $item['snippet']['title'] : '';
            $link['description'] = array_key_exists('description', $item['snippet']) ? $item['snippet']['description'] : '';
            $link['resourceItemId'] = array_key_exists('id', $item) ? $item['id'] : null;
            $link['timestamp'] = $timestamp;
            $link['resource'] = 'google';

            $parsed[] = $link;
        }

        return $parsed;
    }
}