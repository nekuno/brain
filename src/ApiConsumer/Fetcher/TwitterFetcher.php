<?php

namespace ApiConsumer\Fetcher;

class TwitterFetcher extends BasicPaginationFetcher
{
    protected $url = 'statuses/user_timeline.json';

    protected $paginationField = 'since_id';

    protected $pageLength = 200;

    protected function getQuery()
    {
        return array(
            'count' => $this->pageLength,
            'trim_user' => 'true',
            'exclude_replies' => 'true',
            'contributor_details' => 'false',
            'include_rts' => 'false',
        );
    }

    protected function getItemsFromResponse($response)
    {
        return $response;
    }

    protected function getPaginationIdFromResponse($response)
    {
        $paginationId = null;

        $itemsCount = count($response);
        if ($itemsCount > 0 && $itemsCount > $this->pageLength) {
            $lastItem = $response[count($response) - 1];
            $paginationId = $lastItem['id_str'];
        } else {
            return null;
        }

        return $paginationId;
    }

    /**
     * @param $rawFeed array
     * @return array
     */
    protected function parseLinks(array $rawFeed)
    {
        $formatted = array();

        foreach ($rawFeed as $item) {
            if (empty($item['entities']) || empty($item['entities']['urls'][0])) {
                continue;
            }

            $url = $item['entities']['urls'][0]['expanded_url']
                ? $item['entities']['urls'][0]['expanded_url']
                : $item['entities']['urls'][0]['url'];

            $timestamp = null;
            if (array_key_exists('created_at', $item)) {
                $date = new \DateTime($item['created_at']);
                $timestamp = ($date->getTimestamp()) * 1000;
            }

            $link = array();
            $link['url'] = $url;
            $link['title'] = array_key_exists('text', $item) ? $item['text'] : null;
            $link['description'] = null;
            $link['resourceItemId'] = array_key_exists('id', $item) ? $item['id'] : null;
            $link['timestamp'] = $timestamp;
            $link['resource'] = 'twitter';

            $formatted[] = $link;
        }

        return $formatted;
    }
}