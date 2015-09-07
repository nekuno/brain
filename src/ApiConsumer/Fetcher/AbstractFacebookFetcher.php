<?php

namespace ApiConsumer\Fetcher;

abstract class AbstractFacebookFetcher extends BasicPaginationFetcher
{
    protected $paginationField = 'after';

    protected $pageLength = 200;

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
            $url = isset($item['link']) ? $item['link'] : null;
            if (null === $url) {
                continue;
            }
            $id = $item['id'];
            $parsed[] = $this->getLinkArrayFromUrl($url, $id, $item);

            //if it's a like with website outside facebook
            if (isset($item['website'])) {
                $website = $item['website'];

                $website = str_replace('\n', ' ', $website);
                $website = str_replace(', ', ' ', $website);

                preg_match_all('/(https?\:\/\/[^\" ]+)|(www\.[a-z][-a-z0-9]+\.[a-z]+(\.[a-z]{2,2})?)/i', $website, $matches);
                $websiteUrlsArray = $matches[0];

                $counter = 1;
                foreach ($websiteUrlsArray as $websiteUrl) {
                    if (substr($websiteUrl, 0, 3) == 'www') {
                        $websiteUrl = 'http://' . $websiteUrl;
                    }
                    $parsed[] = $this->getLinkArrayFromUrl(trim($websiteUrl), $id . '-' . $counter, $item);
                    $counter++;
                }
            }
        }

        return $parsed;
    }

    /**
     * @param $url
     * @param $id
     * @param $item
     * @return array
     */
    private function getLinkArrayFromUrl($url, $id, $item)
    {
        $link = array();

        $parts = parse_url($url);
        if (!isset($parts['host']) && isset($parts['path'])){
            var_dump($item);
        }
        $link['url'] = !isset($parts['host']) && isset($parts['path']) ? 'https://www.facebook.com' . $parts['path'] : $url;
        $link['title'] = null;
        $link['description'] = null;
        $link['resourceItemId'] = $id;

        $link['types'] = array();
        if (array_key_exists('attachments', $item)) {
            foreach ($item['attachments']['data'] as $attachment) {
                    $link['types'][]=$attachment['type'];
            }
        }

        $timestamp = null;
        if (array_key_exists('created_time', $item)) {
            $date = new \DateTime($item['created_time']);
            $timestamp = ($date->getTimestamp()) * 1000;
        }
        $link['timestamp'] = $timestamp;

        $link['resource'] = 'facebook';

        return $link;
    }
}