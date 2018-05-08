<?php

namespace ApiConsumer\LinkProcessor\UrlParser;


use ApiConsumer\Exception\UrlNotValidException;

class FacebookUrlParser extends UrlParser
{
    const FACEBOOK_PAGE = 'facebook_page';
    //Types managed outside of here:
    const FACEBOOK_VIDEO = 'facebook_video';
    const FACEBOOK_PROFILE = 'facebook_profile';
    const FACEBOOK_STATUS = 'facebook_status';
    const DEFAULT_IMAGE_PATH = 'default_images/facebook.png';

    static function FACEBOOK_VIDEO_TYPES()
    {
        return array('video_inline', 'video_autoplay');
    }

    static function FACEBOOK_PAGE_TYPES()
    {
        return array('avatar');
    }

    public function getUrlType($url)
    {
        if ($this->isFacebookPage($url)) {
            return self::FACEBOOK_PAGE;
        } else if ($this->isFacebookUrl($url)) {
            return self::FACEBOOK_STATUS;
        }

        throw new UrlNotValidException($url);
    }

    public function getVideoId($url)
    {
        $prefix = 'videos/';
        $startPos = strpos($url, $prefix);
        if ($startPos === false) {
            throw new UrlNotValidException($url);
        }

        return substr($url, $startPos + strlen($prefix));
    }

    /**
     * Returns true on Facebook Pages AND on Facebook user profiles
     * @param $url
     * @return bool
     */
    protected function isFacebookPage($url)
    {
        $reserved_urls = array('photo.php', 'settings', 'support', '#', 'groups', 'help');

        $parts = parse_url($url);
        if (!isset($parts['path']) || !isset($parts['host'])) {
            return false;
        }

        $path = explode('/', $parts['path']);

        if ($parts['host'] === 'www.facebook.com' &&
            (count($path) === 2 || count($path) === 3 && !$path[2]) &&
            !in_array($path[1], $reserved_urls)) {
            return true;
        }

        if ($parts['host'] === 'www.facebook.com' && $path[1] === "pages") {
            return true;
        }

        return false;
    }

    public function isStatusId($id){
        return strpos($id, '_') !== false;
    }

    public function isFacebookUrl($url)
    {
        $parsedUrl = parse_url($url);
        if (preg_match('/^https?:\/\/(www\.)?facebook\.com\//i', $parsedUrl['host'])) {
            return true;
        }

        return false;
    }
}