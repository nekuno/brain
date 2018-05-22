<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use Model\Link\Link;

abstract class AbstractFacebookFetcher extends BasicPaginationFetcher
{
    protected $paginationField = 'after';

    protected $pageLength = 200;

    protected $paginationId = null;

    /**
     * @inheritdoc
     */
    public function getUrl($userId = null)
    {
        return $userId ?: $this->token->getResourceId();
    }

    /**
     * @inheritdoc
     */
    protected function getQuery($paginationId = null)
    {
        $query = parent::getQuery($paginationId);

        return array_merge(
            $query,
            array('limit' => $this->pageLength)
        );
    }

    /**
     * @inheritdoc
     */
    protected function getItemsFromResponse($response)
    {
        return isset($response['data']) ? $response['data'] : array();
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

            $parsedLink = $this->build($url, $id, $item);
            $this->addAdditionalType($parsedLink, $item);
            $parsed[] = $parsedLink;

            //if it's a like with website outside facebook
            if (isset($item['website'])) {
                $website = $item['website'];

                $website = str_replace('\n', ' ', $website);
                $website = str_replace(', ', ' ', $website);
                $websiteUrlsArray = (new FacebookUrlParser())->extractURLsFromText($website);

                $counter = 1;
                foreach ($websiteUrlsArray as $websiteUrl) {
                    if (substr($websiteUrl, 0, 3) == 'www') {
                        $websiteUrl = 'http://' . $websiteUrl;
                    }

                    $thisId = $id . '-' . $counter;

                    $new = $this->build(trim($websiteUrl), $thisId, $item);
                    $new->setType(UrlParser::SCRAPPER);
                    $parsed[] = $new;
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
     * @return PreprocessedLink
     */
    private function build($url, $id, $item)
    {
        $link = new Link();

        $parts = parse_url($url);
        $link->setUrl(!isset($parts['host']) && isset($parts['path']) ? 'https://www.facebook.com' . $parts['path'] : $url);

        $timestamp = null;
        if (array_key_exists('created_time', $item)) {
            $date = new \DateTime($item['created_time']);
            $timestamp = ($date->getTimestamp()) * 1000;
        }
        $link->setCreated($timestamp);

        $parsedLink = new PreprocessedLink($link->getUrl());
        $parsedLink->setResourceItemId($id);
        $parsedLink->setFirstLink($link);
        $parsedLink->setSource($this->resourceOwner->getName());

        return $parsedLink;
    }

    protected function addAdditionalType(PreprocessedLink $link, $item)
    {
        if (array_key_exists('attachments', $item)) {
            foreach ($item['attachments']['data'] as $attachment) {
                if (in_array($attachment['type'], FacebookUrlParser::FACEBOOK_VIDEO_TYPES())) {
                    $link->setType(FacebookUrlParser::FACEBOOK_VIDEO);
                }
                if (in_array($attachment['type'], FacebookUrlParser::FACEBOOK_PAGE_TYPES())) {
                    $link->setType(FacebookUrlParser::FACEBOOK_PAGE);
                }
            }
        }
    }
}