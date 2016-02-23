<?php

namespace ApiConsumer\LinkProcessor;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class LinkAnalyzer
{

    const YOUTUBE = 'youtube';
    const SPOTIFY = 'spotify';
    const FACEBOOK = 'facebook';
    const TWITTER = 'twitter';
    const SCRAPPER = 'scrapper';

    /**
     * @param $url
     * @return string
     */
    public function getProcessor($url)
    {
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return self::YOUTUBE;
        }

        if (strpos($url, 'spotify.com') !== false) {
            return self::SPOTIFY;
        }

        if (strpos($url, 'facebook.com') !== false) {
            return self::FACEBOOK;
        }

        if (strpos($url, 'twitter.com') !== false) {
            return self::TWITTER;
        }

        return self::SCRAPPER;
    }
}