<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;
use Service\LookUp\LookUp;

class TwitterUrlParser extends UrlParser
{
    const TWITTER_INTENT = 'twitter_intent';
    const TWITTER_PROFILE = 'twitter_profile';
    const TWITTER_PIC = 'twitter_pic';
    const TWITTER_TWEET = 'twitter_tweet';

    const DEFAULT_IMAGE_PATH = 'default_images/twitter.png';

    public function getUrlType($url)
    {
        if ($this->isTwitterImageUrl($url)) {
            return self::TWITTER_PIC;
        }
        try {
            $this->getStatusId($url);

            return self::TWITTER_TWEET;
        } catch (\Exception $e) {
        }

        try {
            $this->getProfileIdFromIntentUrl($url);

            return self::TWITTER_INTENT;
        } catch (\Exception $e) {
        }

        try {
            $this->getProfileNameFromProfileUrl($url);

            return self::TWITTER_PROFILE;
        } catch (\Exception $e) {
        }

        throw new UrlNotValidException($url);
    }

    /**
     * @param $url
     * @return array
     */
    public function getProfileId($url)
    {
        try {
            $intentId = $this->getProfileIdFromIntentUrl($url);

            return $intentId;
        } catch (\Exception $e) {
        }

        try {
            $profileName = $this->getProfileNameFromProfileUrl($url);

            return $profileName;
        } catch (\Exception $e) {
        }

        throw new UrlNotValidException($url);
    }

    protected function getProfileIdFromIntentUrl($url)
    {
        $parts = parse_url($url);

        if (isset($parts['path']) && isset($parts['query'])) {

            $path = explode('/', trim($parts['path'], '/'));
            parse_str($parts['query'], $qs);

            if (!empty($path) && $path[0] === 'intent') {
                if (isset($qs['user_id'])) {
                    return array('user_id' => $qs['user_id']);
                } else {
                    if (isset($qs['screen_name'])) {
                        return array('screen_name' => $qs['screen_name']);
                    }
                }
            }

        }

        throw new UrlNotValidException($url);
    }

    protected function getProfileNameFromProfileUrl($url)
    {
        $parts = parse_url($url);

        if (isset($parts['path'])) {

            $path = explode('/', trim($parts['path'], '/'));

            $reserved = array(
                'i',
                'intent',
                'hashtag',
                'search',
                'search-home',
                'who_to_follow',
                'about',
                'tos',
                'privacy',
                'settings',
                '#',
                'login'
            );

            if (!empty($path) && !in_array($path[0], $reserved) && !isset($path[1])) {
                return array('screen_name' => $path[0]);
            }
        }

        throw new UrlNotValidException($url);
    }

    public function getStatusId($url)
    {
        $parts = parse_url($url);

        if (isset($parts['path'])) {

            $path = explode('/', trim($parts['path'], '/'));

            if (count($path) >= 2 && $path[1] == 'status' && is_numeric($path[2])) {
                return (int)$path[2];
            }
        }

        throw new UrlNotValidException($url);
    }

    private function isTwitterUrl($url)
    {
        $parts = parse_url($url);

        if (!isset($parts['host'])) {
            throw new UrlNotValidException($url);
        }

        $host = explode('.', $parts['host']);

        return in_array('twitter', array($host[0], $host[1]));
    }

    private function isTwitterImageUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host === 'pic.twitter.com';
    }

    public function extractURLsFromText($string)
    {
        $urls = parent::extractURLsFromText($string);

        foreach ($urls as $key => $url) {
            if (strpos($url, 'pic.twitter.com') > 0) {
                $urls[$key] = substr($url, strpos($url, 'pic.twitter.com'));
            }
        }

        return $urls;
    }

    public function cleanURL($url)
    {
        $url = parent::cleanURL($url);

        $this->fixHost($url);
        $this->fixPath($url);

        if (!$this->isTwitterUrl($url)) {
            throw new UrlNotValidException($url);
        }

        return $url;
    }

    protected function fixHost($url)
    {
        $parts = parse_url($url);

        if (isset($parts['host']) && $parts['host'] === 'www.twitter.com') {
            $parts['host'] = 'twitter.com';
        }

        return http_build_url($parts);
    }

    protected function fixPath($url)
    {
        $parts = parse_url($url);

        if (isset($parts['path'])) {
            $path = explode('/', trim($parts['path'], '/'));
            if (isset($path[3]) && in_array($path[3], array('photo', 'video'))) {
                $path = array_slice($path, 0, 3);
                $parts['path'] = implode('/', $path);
            }

            return http_build_url($parts);
        }

        return $url;
    }

    public function checkUrlValid($url, $urlDecoded = null)
    {
        parent::checkUrlValid($url, $urlDecoded);

        if (!$this->isTwitterUrl($url)) {
            throw new UrlNotValidException($urlDecoded ?: $url);
        }
    }

    public function buildUserUrl($screenName)
    {
        return LookUp::TWITTER_BASE_URL . $screenName;
    }

    public function getOriginalProfileUrl($data, $default)
    {
       return $this->getProfileUrl($data, $default, '');
    }

    public function getSmallProfileUrl($data, $default)
    {
        return $this->getProfileUrl($data, $default);
    }

    public function getMediumProfileUrl($data, $default)
    {
        return $this->getProfileUrl($data, $default, '_bigger');
    }

    protected function getProfileUrl($data, $default, $replacement = null)
    {
        $url =  isset($data['profile_image_url']) ? $data['profile_image_url'] : $default;
        if (null !== $replacement)
        {
            $url = str_replace('_normal', $replacement, $url);
        }

        return $url;
    }
}