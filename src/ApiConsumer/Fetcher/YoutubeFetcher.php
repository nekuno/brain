<?php

namespace ApiConsumer\Fetcher;

class YoutubeFetcher extends BasicPaginationFetcher
{
    protected $paginationField = 'pageToken';

    protected $pageLength = 20;

    public function getUrl()
    {
        return 'youtube/v3/activities';
    }

    protected function getQuery()
    {
        return array(
            'maxResults' => $this->pageLength,
            'mine' => 'true',
            'part' => 'snippet,contentDetails'
        );
    }

    protected function getItemsFromResponse($response)
    {
        return $response['items']?:array();
    }

    protected function getPaginationIdFromResponse($response)
    {
        $paginationId = null;

        if (array_key_exists('nextPageToken', $response)) {
            $paginationId = $response['nextPageToken'];
        }

        return $paginationId;
    }

    protected function getYoutubeUrlFromResourceId($resourceId)
    {
        $url = "";
        if (isset($resourceId['kind'])) {
            switch ($resourceId['kind']) {
                
                case 'youtube#video':
                    if (isset($resourceId['videoId'])) { 
                        $url = 'https://www.youtube.com/watch?v='.$resourceId['videoId'];
                    }
                    break;

                case 'youtube#channel':
                    if (isset($resourceId['channelId'])) { 
                        $url = 'https://www.youtube.com/channel/'.$resourceId['channelId'];
                    }
                    break;

                case 'youtube#playlist':
                    if (isset($resourceId['playlistId'])) { 
                        $url = 'https://www.youtube.com/playlist?list='.$resourceId['playlistId'];
                    }
                    break;                
            }
        }

        return $url;
    }

    protected function generateYoutubeUrl($item)
    {
        $url = "";
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
                $activity = 'like';
            case 'favorite':
                $activity = 'favorite';
            case 'subscription':
                $activity = 'subscription';
            case 'playlistItem':
                $activity = 'playlistItem';
            case 'recommendation':
                $activity = 'recommendation';
            case 'social':
                $activity = 'social';

                if (isset($item['contentDetails'][$activity]['resourceId'])) {
                    $url = $this->getYoutubeUrlFromResourceId($item['contentDetails'][$activity]['resourceId']);
                }
                break;

            case 'bulletin':
            case 'channelItem':
            default:
                break;
        }

        return $url;
    }

    /**
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

            $link['url']            = $url;
            $link['title']          = array_key_exists('title', $item['snippet'])?$item['snippet']['title']:'';
            $link['description']    = array_key_exists('description', $item['snippet'])?$item['snippet']['description']:'';
            $link['resourceItemId'] = array_key_exists('id', $item) ? $item['id'] : null;
            $link['resource']       = 'google';

            $parsed[] = $link;
        }

        return $parsed;
    }
}