<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

class SpotifyUrlParser extends UrlParser
{
    const TRACK_URL = 'track';
    const ALBUM_URL = 'album';
    const ALBUM_TRACK_URL = 'album_track';
    const ARTIST_URL = 'artist';

    public function getUrlType($url)
    {

        if (!$this->isUrlValid($url)) {
            return false;
        }

        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));

            if (count($path) > 1) {
                if ($path[0] === self::TRACK_URL || $path[0] === self::ALBUM_URL || $path[0] === self::ARTIST_URL) {
                    if ($path[0] === self::ALBUM_URL && count($path) == 3) {
                        return self::ALBUM_TRACK_URL;
                    }
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

        if (!$this->isUrlValid($url)) {
            return false;
        }
        
        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));

            if (count($path) > 1) {
                if ($path[0] === self::ALBUM_URL && count($path) == 3) {
                    return $path[2];
                }
                return $path[1];
            }
        }

        return false;
    }

    public function cleanURL($url)
    {
        $url =  parent::cleanURL($url);

        $type = $this->getUrlType($url);

        switch($type){
            case $this::TRACK_URL:
            case $this::ALBUM_TRACK_URL:
                $url = $this->buildTrackURL($url);
                break;
        }

        return $url;
    }

    private function buildTrackURL($url)
    {
        $parts = parse_url($url);
        if (!isset($parts['path'])){
            return false;
        }

        $route = explode('/', $parts['path']);
        $id = end($route);

        $url = 'https://open.spotify.com/track/' . $id;

        return $url;
    }

} 