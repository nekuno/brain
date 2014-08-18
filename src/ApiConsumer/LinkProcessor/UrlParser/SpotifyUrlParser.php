<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

class SpotifyUrlParser
{
    const TRACK_URL = 'track';
    const ALBUM_URL = 'album';
    const ARTIST_URL = 'artist';

    public function getUrlType($url)
    {
        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));

            if (count($path) === 2) {
                if ($path[0] === self::TRACK_URL || $path[0] === self::ALBUM_URL || $path[0] === self::ARTIST_URL) {
                    return $path[0];
                }
            }
        }

        return false;
    }

    /**
     * Get Spotify ID from URL
     *
     * @param string $url
     * @return mixed spotify ID or FALSE if not found
     */
    public function getSpotifyIdFromUrl($url)
    {
        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));
            
            if (count($path) === 2) {
                return $path[1];
            }
        }

        return false;
    }
} 