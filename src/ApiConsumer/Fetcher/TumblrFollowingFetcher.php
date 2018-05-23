<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use Model\Link\Creator;

class TumblrFollowingFetcher extends BasicPaginationFetcher
{
    protected $url = 'user/following';

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
        return isset($response['response']['blogs']) ? $response['response']['blogs'] : array();
    }

    protected function getPaginationIdFromResponse($response)
    {
        if (isset($response['response']['total_blogs']) && $response['response']['total_blogs'] === 0) {
            $this->currentOffset = null;
        }
        if (isset($response['response']['blogs'])) {
            $this->currentOffset += count($response['response']['blogs']);
        }
        if (isset($response['response']['total_blogs']) && $this->currentOffset >= $response['response']['total_blogs']) {
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
            $id = TumblrUrlParser::getBlogId($item['url']);
            $link = new Creator();
            $link->setUrl($item['url']);
            $link->setTitle($item['title']);
            $link->setDescription($item['description']);
            $link->setCreated($item['updated']);

            $preprocessedLink = new PreprocessedLink($link->getUrl());
            $preprocessedLink->setFirstLink($link);
            $preprocessedLink->setType(TumblrUrlParser::TUMBLR_BLOG);
            $preprocessedLink->setSource($this->resourceOwner->getName());
            $preprocessedLink->setResourceItemId($id);
            $preprocessedLinks[] = $preprocessedLink;
        }

        return $preprocessedLinks;
    }
}