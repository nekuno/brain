<?php

namespace ApiConsumer\Fetcher;

abstract class AbstractFacebookFetcher extends BasicPaginationFetcher
{
    protected $paginationField = 'after';

    protected $pageLength = 20;

    protected $paginationId = null;

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->user['facebookID'];
    }

    /**
     * @inheritdoc
     */
    protected function getQuery()
    {
        return array(
            'limit' => $this->pageLength,
        );
    }

    /**
     * @inheritdoc
     */
    protected function getItemsFromResponse($response)
    {
        return $response['data'] ?: array();
    }

    /**
     * @inheritdoc
     */
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
     * @inheritdoc
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

    /**
     * Get the link array from a url and an id
     *
     * @param $url
     * @param $id
     * @return array
     */
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