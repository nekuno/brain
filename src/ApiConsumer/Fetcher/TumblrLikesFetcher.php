<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use Model\Link\Link;

class TumblrLikesFetcher extends BasicPaginationFetcher
{
    protected $url = 'user/likes';

    protected $paginationField = 'offset';

    protected $pageLength = 1000;

    protected $currentOffset = 0;

    protected function getQuery($paginationId = null)
    {
        return array_merge(
            parent::getQuery($paginationId),
            array(
                'limit' => $this->pageLength,
                'offset' => $this->currentOffset,
            )
        );
    }

    protected function getItemsFromResponse($response)
    {
        return isset($response['response']['liked_posts']) ? $response['response']['liked_posts'] : array();
    }

    protected function getPaginationIdFromResponse($response)
    {
        if (isset($response['response']['liked_count']) && $response['response']['liked_count'] === 0) {
            $this->currentOffset = null;
        }
        if (isset($response['response']['liked_posts'])) {
            $this->currentOffset += count($response['response']['liked_posts']);
        }
        if (isset($response['response']['liked_count']) && $this->currentOffset >= $response['response']['liked_count']) {
            $this->currentOffset = null;
        }

        return $this->currentOffset;
    }

    /**
     * @inheritdoc
     */
    protected function parseLinks(array $response)
    {
        $preprocessedLinks = array();

        foreach ($response as $item) {
            if (!$type = $this->getType($item)) {
                continue;
            }
            $link = new Link();
            $link->setId($item['id']);
            $link->setUrl($item['post_url']);
            $link->setCreated($item['timestamp']);

            $preprocessedLink = new PreprocessedLink($link->getUrl());
            $preprocessedLink->setFirstLink($link);
            $preprocessedLink->setType($type);
            $preprocessedLink->setSource($this->resourceOwner->getName());
            $preprocessedLink->setResourceItemId($item['blog_name']);
            $preprocessedLinks[] = $preprocessedLink;
        }

        return $preprocessedLinks;
    }

    private function getType($post)
    {
        switch ($post['type']) {
            case 'audio':
                return TumblrUrlParser::TUMBLR_AUDIO;
            case 'video':
                return TumblrUrlParser::TUMBLR_VIDEO;
            case 'photo':
                return TumblrUrlParser::TUMBLR_PHOTO;
            case 'link':
                return TumblrUrlParser::TUMBLR_LINK;
        }

        return null;
    }
}