<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use ApiConsumer\ResourceOwner\TumblrResourceOwner;
use Model\Link\Link;
use Model\Token\Token;

class TumblrPostsFetcher extends AbstractFetcher
{

    public function fetchLinksFromUserFeed(Token $token)
    {
        $this->setToken($token);

        $response = $this->resourceOwner->requestAsUser('user/info', array(), $token);

        $blogs = isset($response['response']['user']['blogs']) ? $response['response']['user']['blogs'] : array();
        $responsePosts = array();

        foreach ($blogs as $blog) {
            if (isset($blog['type']) && $blog['type'] === 'private') {
                continue;
            }
            $id = TumblrUrlParser::getBlogId($blog['url']);
            /** @var TumblrResourceOwner $resourceOwner */
            $resourceOwner = $this->resourceOwner;
            $posts = $resourceOwner->requestPosts($id, $token);
            if (isset($posts['response']['posts']) && is_array($posts['response']['posts'])) {
                foreach ($posts['response']['posts'] as $post) {
                    $responsePosts[] = $post;
                }
            }
        }

        return $this->parseLinks($responsePosts);
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
            $preprocessedLink->setToken($this->token);
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