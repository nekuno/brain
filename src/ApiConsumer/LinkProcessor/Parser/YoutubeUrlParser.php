<?php

namespace ApiConsumer\LinkProcessor\Parser;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class YoutubeUrlParser
{

    const VIDEO_URL = 'video';
    const CHANNEL_URL = 'channel';
    const PLAYLIST_URL = 'playlist';

    public function getUrlType($url)
    {
        if ($this->getYoutubeIdFromUrl($url)) {
            return self::VIDEO_URL;
        }

        if ($this->getChannelIdFromUrl($url)) {
            return self::CHANNEL_URL;
        }
        if ($this->getPlaylistIdFromUrl($url)) {
            return self::PLAYLIST_URL;
        }

        return false;
    }

    /**
     * Get Youtube video ID from URL
     *
     * @param string $url
     * @return mixed Youtube video ID or FALSE if not found
     */
    public function getYoutubeIdFromUrl($url)
    {

        $parts = parse_url($url);

        $path = explode('/', trim($parts['path'], '/'));

        if (!empty($path)) {
            if (in_array($path[0], array('channel', 'playlist', 'view_play_list'))) {
                return false;
            }
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $qs);
            if (isset($qs['v'])) {
                return $qs['v'];
            } else if (isset($qs['vi'])) {
                return $qs['vi'];
            }
        }

        if (isset($parts['path'])) {
            $path = explode('/', trim($parts['path'], '/'));
            if (count($path) >= 2 && in_array($path[0], array('v', 'vi'))) {
                return $path[1];
            }
            if (count($path) === 1) {
                return $path[0];
            }
        }

        return false;
    }

    /**
     * Get Youtube channel ID from URL
     *
     * @param string $url
     * @return mixed
     */
    public function getChannelIdFromUrl($url)
    {

        $parts = parse_url($url);

        $path = explode('/', trim($parts['path'], '/'));

        if (!empty($path) && $path[0] === 'channel' && $path[1]) {
            return $path[1];
        }

        return false;
    }

    /**
     * Get Youtube playlist ID from URL
     *
     * @param string $url
     * @return mixed
     */
    public function getPlaylistIdFromUrl($url)
    {

        $parts = parse_url($url);

        $path = explode('/', trim($parts['path'], '/'));

        if (!empty($path) && $path[0] === 'playlist') {
            if (isset($parts['query'])) {
                parse_str($parts['query'], $qs);
                if (isset($qs['list'])) {
                    return $qs['list'];
                }
            }
        }

        if (!empty($path) && $path[0] === 'view_play_list') {
            if (isset($parts['query'])) {
                parse_str($parts['query'], $qs);
                if (isset($qs['p'])) {
                    return $qs['p'];
                }
            }
        }

        return false;
    }
} 