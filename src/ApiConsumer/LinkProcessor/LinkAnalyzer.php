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
    const SCRAPPER = 'scrapper';

    /**
     * @param $link
     * @return string
     */
    public function getProcessor($link)
    {

        if (strpos($link['url'], 'youtube.com') !== false || strpos($link['url'], 'youtu.be') !== false) {
            return self::YOUTUBE;
        }

        if (strpos($link['url'], 'spotify.com') !== false) {
            return self::SPOTIFY;
        }

        if (strpos($link['url'], 'facebook.com') !== false) {
            return self::FACEBOOK;
        }

        return self::SCRAPPER;
    }
}