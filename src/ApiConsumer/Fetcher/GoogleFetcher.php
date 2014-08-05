<?php

namespace ApiConsumer\Fetcher;

class GoogleFetcher extends BasicPaginationFetcher
{
    protected $paginationField = 'pageToken';

    protected $pageLength = 20;

    public function getUrl()
    {
        return '/plus/v1/people/'.$this->user['googleID'].'/activities/public';
    }

    protected function getQuery()
    {
        return array(
            'maxResults' => $this->pageLength,
            'fields' => 'items(object(attachments(content,displayName,id,objectType,url)),title),nextPageToken'
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

    /**
     * @return array
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