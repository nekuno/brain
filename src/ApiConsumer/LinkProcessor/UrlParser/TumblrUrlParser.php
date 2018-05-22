<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;

class TumblrUrlParser extends UrlParser
{
    const TUMBLR_BLOG = 'tumblr_blog';
    const TUMBLR_AUDIO = 'tumblr_audio';
    const TUMBLR_VIDEO = 'tumblr_video';
    const TUMBLR_PHOTO = 'tumblr_photo';
    const TUMBLR_LINK = 'tumblr_link';
    const TUMBLR_UNKNOWN_TYPE_POST = 'tumblr_unknown_type_post';
    const DEFAULT_IMAGE_PATH = 'default_images/tumblr.png';

    public function getUrlType($url)
    {
        if ($this->isBlogUrl($url)) {
            return self::TUMBLR_BLOG;
        }
        if ($this->isPostUrl($url)) {
            return self::TUMBLR_UNKNOWN_TYPE_POST;
        }

        throw new UrlNotValidException($url);
    }

    static public function getBlogId($url)
    {
        $urlParsed = parse_url($url);

        if (isset($urlParsed['path'])) {
            $path = explode('/', trim($urlParsed['path'], '/'));

            if (count($path) > 1 && $path[0] === 'blog' && $id = $path[1]) {
                return $id;
            }
        }

        if (!isset($urlParsed['host'])) {
            throw new UrlNotValidException($url);
        }

        return $urlParsed['host'];
    }

    static public function fixUrl($url)
    {
        $urlParsed = parse_url($url);

        if (isset($urlParsed['query']) && substr($urlParsed['query'], 0, 12) === "redirect_to=") {
            $redirectPath = substr($urlParsed['query'], 12);
            $url = $urlParsed['scheme'] . '://' . $urlParsed['host'] . $redirectPath;
        }

        return $url;
    }

    static public function getPostId($url)
    {
        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));

            if (count($path) > 1 && ($path[0] === 'post' || $path[0] === 'image') && $id = $path[1]) {
                return $id;
            }
        }

        throw new UrlNotValidException($url);
    }

    static public function getPostProcessor($post)
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

    public function isBlogUrl($url)
    {
        if (!$this->isTumblrUrl($url)) {
            throw new UrlNotValidException($url);
        }

        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['query']) && substr($parsedUrl['query'], 0, 12) === "redirect_to=") {
            $redirectPath = substr($parsedUrl['query'], 12);
            $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $redirectPath;
            $parsedUrl = parse_url($url);
        }

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));

            if (count($path) > 0 && $path[0] !== 'blog') {
                return false;
            }
        }

        return true;
    }

    public function isPostUrl($url)
    {
        if (!$this->isTumblrUrl($url)) {
            throw new UrlNotValidException($url);
        }

        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));

            if (count($path) > 0 && ($path[0] === 'post' || $path[0] === 'image')) {
                return true;
            }
        }

        return false;
    }

    public function isTumblrUrl($url)
    {
        $parsedUrl = parse_url($url);
        if (preg_match("/.*tumblr\\.com.*/", $parsedUrl['host'])) {
            return true;
        }

        return false;
    }
}