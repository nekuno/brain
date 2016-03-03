<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace ApiConsumer\LinkProcessor\UrlParser;


class FacebookUrlParser extends UrlParser
{
    const FACEBOOK_VIDEO = 'facebook_video';
    const FACEBOOK_PAGE = 'facebook_page';

    public function getUrlType($url)
    {
        if ($this->isFacebookPage($url)) {
            return self::FACEBOOK_PAGE;
        }

        return false;
    }

    /**
     * Returns true on Facebook Pages AND on Facebook profiles
     * @param $url
     * @return bool
     */
    protected function isFacebookPage($url)
    {
        $reserved_urls = array('photo.php', 'settings', 'support', '#', 'groups', 'help');

        $parts = parse_url($url);
        $path = explode('/', $parts['path']);

        if ($parts['host'] === 'www.facebook.com' && count($path) == 2 && !in_array($path[1], $reserved_urls)){
            return true;
        }

        return false;
    }
}