<?php

namespace ApiConsumer\LinkProcessor\UrlParser;


class TwitterUrlParser extends UrlParser
{
    const TWITTER_INTENT = 'intent';
    const TWITTER_PROFILE = 'profile';
    const TWITTER_IMAGE = 'image';
    const TWITTER_TWEET = 'tweet';

    public function getUrlType($url)
    {
        if ($this->isTwitterImageUrl($url)) {
            return self::TWITTER_IMAGE;
        }
        if ($this->getStatusIdFromTweetUrl($url)) {
            return self::TWITTER_TWEET;
        }
        if ($this->getProfileIdFromIntentUrl($url)) {
            return self::TWITTER_INTENT;
        }
        if ($this->getProfileNameFromProfileUrl($url)) {
            return self::TWITTER_PROFILE;
        }

        return false;
    }

    public function getProfileIdFromIntentUrl($url)
    {
        if (!$this->isUrlValid($url)) {
            return false;
        }

        $parts = parse_url($url);

        if (isset($parts['path']) && isset($parts['query'])) {

            $path = explode('/', trim($parts['path'], '/'));
            parse_str($parts['query'], $qs);

            if (!empty($path) && $path[0] === 'intent') {
                if (isset($qs['user_id'])) {
                    return array('user_id' => $qs['user_id']);
                } else if (isset($qs['screen_name'])) {
                    return array('screen_name' => $qs['screen_name']);
                }
            }

        }

        return false;
    }

    public function getProfileNameFromProfileUrl($url)
    {
        if (!$this->isUrlValid($url)) {
            return false;
        }

        $parts = parse_url($url);

        if (isset($parts['host'])) {
            $host = explode('.', $parts['host']);
            if ($host[0] !== 'twitter') {
                return false;
            }
        }

        if (isset($parts['path'])) {

            $path = explode('/', trim($parts['path'], '/'));

            $reserved = array('i', 'intent', 'hashtag', 'search',
                'who_to_follow', 'about', 'tos', 'privacy', 'settings', '#');

            if (!empty($path) && !in_array($path[0], $reserved) && !isset($path[1])) {
                return $path[0];
            }
        }

        return false;
    }

    public function getStatusIdFromTweetUrl($url)
    {
        $parts = parse_url($url);
        if (isset($parts['host'])) {

            $host = explode('.', $parts['host']);
            if ($host[0] !== 'twitter') {
                return false;
            }
        }
        if (isset($parts['path'])) {

            $path = explode('/', trim($parts['path'], '/'));

            if (count($path) >= 2 && $path[1] == 'status' && is_numeric($path[2])) {
                return (int)$path[2];
            }
        }

        return false;
    }

    private function isTwitterImageUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host === 'pic.twitter.com';
    }

}