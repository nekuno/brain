<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

class InstagramUrlParser extends UrlParser
{
    const INSTAGRAM = 'instagram';
    const INSTAGRAM_PROFILE = 'instagram_profile';
    const DEFAULT_IMAGE_PATH = 'default_images/instagram.png';

    public function getUrlType($url)
    {
        if ($this->isProfileUrl($url)) {
            return self::INSTAGRAM_PROFILE;
        }

        return self::INSTAGRAM;
    }

    public function isProfileUrl($url)
    {
        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));

            if (count($path) === 1) {
                return true;
            }
        }

        return false;
    }
}