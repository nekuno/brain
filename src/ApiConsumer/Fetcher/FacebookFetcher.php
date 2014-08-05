<?php

namespace ApiConsumer\Fetcher;

class FacebookFetcher extends BasicPaginationFetcher
{
    protected $paginationField = 'after';

    protected $pageLength = 20;

    public function getUrl()
    {
        return $this->user['facebookID'].'/links';
    }

    protected function getQuery()
    {
        return array(
            'limit' => $this->pageLength,
        );
    }

    protected function getItemsFromResponse($response)
    {
        return $response['data']?:array();
    }

    protected function getPaginationIdFromResponse($response)
    {
        $paginationId = null;

        if (array_key_exists('paging', $response)) {
            if (array_key_exists('cursors', $response['paging'])) {
                $paginationId = $response['paging']['cursors']['after'];
            }
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
            $link['url']            = $item['link'];
            $link['title']          = array_key_exists('name', $item) ? $item['name'] : null;
            $link['description']    = array_key_exists('description', $item) ? $item['description'] : null;
            $link['resourceItemId'] = array_key_exists('id', $item) ? (int)$item['id'] : null;
            $link['resource']       = 'facebook';

            $parsed[] = $link;
        }

        return $parsed;
    }
}