<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace ApiConsumer\LinkProcessor\UrlParser;


class FacebookUrlParser extends UrlParser
{
    const FACEBOOK_VIDEO = 'facebook_video';
    const FACEBOOK_PROFILE = 'facebook_profile';

    public function getUrlType($url)
    {
        if ($this->isFacebookProfile($url)) {
            return self::FACEBOOK_PROFILE;
        }

        return false;
    }

    /**
     * Returns true on Facebook Pages AND on Facebook user profiles
     * @param $url
     * @return bool
     */
    protected function isFacebookProfile($url)
    {
        $reserved_urls = array('photo.php', 'settings', 'support', '#', 'groups', 'help');

        $parts = parse_url($url);
	    if (!isset($parts['path']) || !isset($parts['host'])) {
		    return false;
	    }

        $path = explode('/', $parts['path']);

        if ($parts['host'] === 'www.facebook.com' && count($path) == 2 && !in_array($path[1], $reserved_urls)){
            return true;
        }

        return false;
    }

    public function isStatusId($id){
        return strpos($id, '_') !== false;
    }
}