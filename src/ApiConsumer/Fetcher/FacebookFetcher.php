<?php

namespace ApiConsumer\Fetcher;

class FacebookFetcher extends BasicPaginationFetcher
{
    protected $paginationField = 'after';

    protected $pageLength = 20;

    protected $paginationId = null;

    public function getUrl()
    {
        return 'me';
    }

    protected function getQuery()
    {
        return array(
            'fields' => 'links{link},likes{link,website}',
            'limit' => $this->pageLength,
        );
    }

    protected function getItemsFromResponse($response)
    {
        return array_merge($response['links']['data'],$response['likes']['data']) ?: array();
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
            $id = $item['id'];
            $parsed[] = $this->getLinkArrayFromUrl($url, $id);

            if (isset($item['website'])) {
                preg_match_all('/https?\:\/\/[^\" ]+/i', $item['website'], $matches);
                $websiteUrlsArray = $matches[0];

                $counter = 1;
                foreach ($websiteUrlsArray as $websiteUrl) {
                    $parsed[] = $this->getLinkArrayFromUrl(trim($websiteUrl), $id.'-'.$counter);
                    $counter++;
                }
            }
        }

        return $parsed;
    }

    private function getLinkArrayFromUrl($url, $id)
    {
        $link = array();

        $parts = parse_url($url);
        $link['url'] = !isset($parts['host']) && isset($parts['path']) ? 'https://www.facebook.com' . $parts['path'] : $url;
        $link['title'] = null;
        $link['description'] = null;
        $link['resourceItemId'] = $id;
        $link['resource'] = 'facebook';

        return $link;
    }
}