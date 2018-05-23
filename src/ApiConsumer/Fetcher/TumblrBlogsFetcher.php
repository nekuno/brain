<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use Model\Link\Creator;
use Model\Token\Token;

class TumblrBlogsFetcher extends AbstractFetcher
{
    protected $url = 'user/info';

    public function fetchLinksFromUserFeed(Token $token)
    {
        $this->setToken($token);

        $response = $this->resourceOwner->requestAsUser($this->url, array(), $token);

        $blogs = isset($response['response']['user']['blogs']) ? $response['response']['user']['blogs'] : array();

        return $this->parseLinks($blogs);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAsClient($username)
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    protected function parseLinks(array $response)
    {
        $preprocessedLinks = array();

        foreach ($response as $item) {
            if (isset($item['type']) && $item['type'] === 'private') {
                continue;
            }
            $id = TumblrUrlParser::getBlogId($item['url']);
            $link = new Creator();
            $link->setUrl($item['url']);

            $preprocessedLink = new PreprocessedLink($link->getUrl());
            $preprocessedLink->setFirstLink($link);
            $preprocessedLink->setType(TumblrUrlParser::TUMBLR_BLOG);
            $preprocessedLink->setSource($this->resourceOwner->getName());
            $preprocessedLink->setResourceItemId($id);
            $preprocessedLink->setToken($this->getToken());
            $preprocessedLinks[] = $preprocessedLink;
        }

        return $preprocessedLinks;
    }
}