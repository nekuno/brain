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

    protected function getYoutubeResourceId($contentDetails)
    {
        foreach ($contentDetails as $activity => $resources) {
            return $resources['resourceId'];
        }
    }

    protected function generateYoutubeUrl($resourceId)
    {
        $url = "";
        switch ($resourceId['kind']) {
            case 'youtube#video':
                $url = 'https://www.youtube.com/watch?v='.$resourceId['videoId'];
                break;
            case 'youtube#channel':
                $url = 'https://www.youtube.com/channel/'.$resourceId['channelId'];
                break;
            default:
                var_dump($resourceId);
                die();
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

            $resourceId = $this->getYoutubeResourceId($item['contentDetails']);
            $url = $this->generateYoutubeUrl($resourceId);
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