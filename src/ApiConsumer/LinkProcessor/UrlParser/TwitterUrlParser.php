<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 27/10/15
 * Time: 13:32
 */

namespace ApiConsumer\LinkProcessor\UrlParser;


class TwitterUrlParser extends UrlParser
{
    const TWITTER_INTENT = 'intent';
    const TWITTER_PROFILE = 'profile';

    public function getUrlType($url)
    {
        if ($this->getProfileIdFromUrl($url)) {
            return self::TWITTER_INTENT;
        }
        if ($this->getProfileNameFromUrl($url)) {
            return self::TWITTER_PROFILE;
        }

        return false;
    }

    public function getProfileIdFromUrl($url)
    {
        if (!$this->isUrlValid($url)) {
            return false;
        }

        $parts = parse_url($url);

        if (isset($parts['path']) && isset($parts['query'])) {

            $path = explode('/', trim($parts['path'], '/'));
            parse_str($parts['query'], $qs);

            if (!empty($path) && $path[0] === 'intent' && isset($qs['user_id'])) {
                return $qs['user_id'];
            }
        }

        return false;
    }

    public function getProfileNameFromUrl($url)
    {
        if (!$this->isUrlValid($url)) {
            return false;
        }

        $parts = parse_url($url);

        if (isset($parts['host'])){
            $host = explode('.', $parts['host']);
            if ($host[0] !== 'twitter'){
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

}