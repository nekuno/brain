<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\InstagramUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\SteamUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use ApiConsumer\LinkProcessor\UrlParser\UrlParserInterface;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use Model\Token\TokensManager;

class LinkAnalyzer
{
    /**
     * @param PreprocessedLink $preprocessedLink
     * @return string
     */
    public static function getProcessorName(PreprocessedLink $preprocessedLink)
    {
        if ($preprocessedLink->getType()) {
            return $preprocessedLink->getType();
        }

        return self::getProcessorNameFromUrl($preprocessedLink);
    }

    /**
     * @param PreprocessedLink $preprocessedLink
     * @return string
     */
    protected static function getProcessorNameFromUrl(PreprocessedLink $preprocessedLink)
    {
        $url = $preprocessedLink->getUrl();

        try {
            $parser = self::getUrlParser($url);
            $type = $parser->getUrlType($url);
        } catch (UrlNotValidException $e){
            $type = UrlParser::SCRAPPER;
        }

        return $type;
    }

    public static function getResource($url)
    {

        if (self::isYouTube($url)) {
            return TokensManager::GOOGLE;
        }

        if (self::isSpotify($url)) {
            return TokensManager::SPOTIFY;
        }

        if (self::isFacebook($url)) {
            return TokensManager::FACEBOOK;
        }

        if (self::isTwitter($url)) {
            return TokensManager::TWITTER;
        }

        if (self::isTumblr($url)) {
            return TokensManager::TUMBLR;
        }
        if (self::isSteam($url)) {
            return TokensManager::STEAM;
        }

        return null;
    }

    public static function mustResolve(PreprocessedLink $link)
    {
        return !self::isSpotify($link->getUrl());
    }

    /**
     * @param $url
     * @return UrlParserInterface|bool
     */
    //TODO: This is functionally a UrlParserFactory. Probably better to create.
    protected static function getUrlParser($url)
    {
        (new UrlParser())->cleanURL($url);

        if (self::isYouTube($url)) {
            return new YoutubeUrlParser();
        }

        if (self::isSpotify($url)) {
            return new SpotifyUrlParser();
        }

        if (self::isFacebook($url)) {
            return new FacebookUrlParser();
        }

        if (self::isTwitter($url)) {
            return new TwitterUrlParser();
        }

        if (self::isInstagram($url)) {
            return new InstagramUrlParser();
        }

        if (self::isTumblr($url)) {
            return new TumblrUrlParser();
        }

        if (self::isSteam($url)) {
            return new SteamUrlParser();
        }

        return new UrlParser();
    }

    public static function cleanUrl($url)
    {
        $parser = self::getUrlParser($url);

        return $parser->cleanURL($url);
    }

    public static function isTextSimilar($text1, $text2)
    {
        $similarTextPercentage = 30;

        similar_text($text1, $text2, $percent);

        return $percent > $similarTextPercentage;
    }

    public static function getUsername($url)
    {
        $parser = self::getUrlParser($url);

        return $parser->getUsername($url);
    }

    private static function isFacebook($url)
    {
        return preg_match('/^https?:\/\/(www\.)?facebook\.com\//i', $url);
    }

    private static function isTwitter($url)
    {
        return preg_match('/^https?:\/\/(www\.|pic\.)?twitter\.com\//i', $url);
    }

    private static function isSpotify($url)
    {
        return preg_match('/^https?:\/\/(open\.|play\.)?spotify\.com\//i', $url);
    }

    private static function isYouTube($url)
    {
        return preg_match('/^https?:\/\/(www\.)?(youtube\.com|youtu\.be)\//i', $url);
    }

    private static function isInstagram($url)
    {
        return preg_match('/^https?:\/\/(www\.)?instagram\.com\//i', $url);
    }

    private static function isTumblr($url)
    {
        return preg_match('/^https?:\/\/.+\.tumblr\.com\//i', $url);
    }

    private static function isSteam($url)
    {
        return preg_match('/^https?:\/\/.+\.steampowered\.com\//i', $url);
    }
}