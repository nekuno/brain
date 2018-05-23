<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Link\Link;

abstract class AbstractTweetsFetcher extends AbstractTwitterFetcher
{

    protected $paginationField = 'max_id';

    protected $pageLength = 200;

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
        $paginationId = isset($lastItem['id_str']) ? $lastItem['id_str'] : null;

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
            $urls  =$this->getUrlsFromResponse($item);

            if (empty($urls)){
                continue;
            } else {
                $url = $urls[0];
            }

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

    protected function getUrlsFromResponse($item){
        $urls = array();
        if (isset($item['entities']) && !empty($item['entities']['urls'])){
            $urls = array_merge($urls, $this->getExpandedUrl($item['entities']['urls']));
        }

        if (isset($item['extended_entities']) && !empty($item['extended_entities']['media'])){
            $urls = array_merge($urls, $this->getExpandedUrl($item['extended_entities']['media']));
        }
        return $urls;
    }

    protected function getExpandedUrl(array $entity)
    {
        $urls = array();
        foreach ($entity as $url){
            $urls[] = $url['expanded_url'];
        }
        return $urls;
    }
}