<?php

namespace ApiConsumer\Fetcher;

class FacebookFetcher extends BasicPaginationFetcher
{
    protected $paginationField = 'after';

    protected $pageLength = 20;

    protected $paginationId = null;

    public function getUrl()
    {
        return $this->user['facebookID'] . '/links';
    }

    protected function getQuery()
    {
        return array(
            'limit' => $this->pageLength,
        );
    }

    protected function getItemsFromResponse($response)
    {
        return $response['data'] ?: array();
    }

    protected function getPaginationIdFromResponse($response)
    {
        $paginationId = null;

        if (isset($response['paging']['cursors']['after'])) {
            $paginationId = $response['paging']['cursors']['after'];
        }

        if ($this->paginationId === $paginationId) {
            return null;
        }

        $this->paginationId = $paginationId;

        return $paginationId;
    }

    /**
     * @return array
     */
    protected function parseLinks(array $rawFeed)
    {
        $parsed = array();

        foreach ($rawFeed as $item) {
            $url = $item['link'];
            $parts = parse_url($url);
            $link['url'] = !isset($parts['host']) && isset($parts['path']) ? 'https://www.facebook.com' . $parts['path'] : $url;
            $link['title'] = array_key_exists('name', $item) ? $item['name'] : null;
            $link['description'] = array_key_exists('description', $item) ? $item['description'] : null;
            $link['resourceItemId'] = array_key_exists('id', $item) ? (int)$item['id'] : null;
            $link['resource'] = 'facebook';

            $parsed[] = $link;
        }

        return $parsed;
    }
}