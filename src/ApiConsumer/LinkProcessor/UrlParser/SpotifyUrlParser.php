<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;

class SpotifyUrlParser extends UrlParser
{
    const TRACK_URL = 'spotify_track';
    const ALBUM_URL = 'spotify_album';
    const ALBUM_TRACK_URL = 'spotify_album_track';
    const ARTIST_URL = 'spotify_artist';
    const DEFAULT_IMAGE_PATH = 'default_images/spotify.png';

    public function getUrlType($url)
    {
        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));

            if (count($path) > 1) {
                switch ($path[0]) {
                    case 'track':
                        return self::TRACK_URL;
                    case 'artist':
                        return self::ARTIST_URL;
                    case 'album':
                        if (count($path) === 3) {
                            return self::ALBUM_TRACK_URL;
                        } else {
                            return self::ALBUM_URL;
                        }

                }
            }
        }

        throw new UrlNotValidException($url);
    }

    /**
     * Get Spotify ID from URL
     *
     * @param string $url
     * @return string spotifyId
     * @throws UrlNotValidException
     */
    public function getSpotifyId($url)
    {
        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));

            if (count($path) <= 1) {
                throw new UrlNotValidException($url);
            }

            switch ($this->getUrlType($url)) {
                case self::ALBUM_TRACK_URL:
                    return $path[2];
                default:
                    return $path[1];
            }
        }

        throw new UrlNotValidException($url);
    }

    public function cleanURL($url)
    {
        $url = parent::cleanURL($url);

        $type = $this->getUrlType($url);

        switch ($type) {
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
        if (!isset($parts['path'])) {
            return false;
        }

        $route = explode('/', $parts['path']);
        $id = end($route);

        $url = 'https://open.spotify.com/track/' . $id;

        return $url;
    }

} 