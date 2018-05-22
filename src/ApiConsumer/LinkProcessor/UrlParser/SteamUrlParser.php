<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;

class SteamUrlParser extends UrlParser
{
    const STEAM_GAME = 'steam_game';
    const DEFAULT_IMAGE_PATH = 'default_images/steam.png';

    public function getUrlType($url)
    {
        if ($this->isGameUrl($url)) {
            return self::STEAM_GAME;
        }

        throw new UrlNotValidException($url);
    }

    static public function getGameId($url)
    {
        $urlParsed = parse_url($url);

        if (isset($urlParsed['path'])) {
            $path = explode('/', trim($urlParsed['path'], '/'));

            if (count($path) > 1 && $path[0] === 'app' && $id = $path[1]) {
                return $id;
            }
        }

        throw new UrlNotValidException($url);
    }

    static public function getGameProcessor()
    {
        return SteamUrlParser::STEAM_GAME;
    }

    public function isGameUrl($url)
    {
        if (!$this->isSteamUrl($url)) {
            throw new UrlNotValidException($url);
        }

        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            $path = explode('/', trim($parsedUrl['path'], '/'));

            if (count($path) > 1 && $path[0] === 'app') {
                return true;
            }
        }

        return false;
    }

    public function isSteamUrl($url)
    {
        $parsedUrl = parse_url($url);
        if (preg_match("/.*store\\.steampowered\\.com.*/", $parsedUrl['host'])) {
            return true;
        }

        return false;
    }
}