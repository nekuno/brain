<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;
use Model\Link;

abstract class AbstractTweetsFetcher extends BasicPaginationFetcher
{

    protected $paginationField = 'max_id';

    protected $pageLength = 200;

    /**
     * @var TwitterResourceOwner
     */
    protected $resourceOwner;

    protected $lastPaginationId = "";

    protected function getItemsFromResponse($response)
    {
        return $response;
    }

    protected function getPaginationIdFromResponse($response)
    {
        if (!is_array($response) || empty($response)) {
            return null;
        }

        $lastItem = end($response);
        $paginationId = isset($lastItem['id_str']) ? $lastItem : null;

        if ($paginationId == $this->lastPaginationId) {
            return null;
        }

        $this->lastPaginationId = $paginationId;

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

            $preprocessedLink = new PreprocessedLink($url);
            $preprocessedLink->setResourceItemId(array_key_exists('id', $item) ? $item['id'] : null); //For intent urls
            $preprocessedLink->setSource($this->resourceOwner->getName());

            $link = new Link();
            $link->setUrl($url);
            $link->setTitle(array_key_exists('text', $item) ? $item['text'] : null);
            $link->setDescription(null);
            $link->setCreated($timestamp);

            $preprocessedLink = new PreprocessedLink($url);
            $preprocessedLink->setFirstLink($link);

            $formatted[] = $preprocessedLink;
        }

        return $formatted;
    }
}