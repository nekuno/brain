<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;


class YoutubeUrlParser extends UrlParser
{
    const VIDEO_URL = 'youtube_video';
    const CHANNEL_URL = 'youtube_channel';
    const PLAYLIST_URL = 'youtube_playlist';
    const GENERAL_URL = 'youtube';
    const DEFAULT_IMAGE_PATH = 'default_images/youtube.png';

    public function getUrlType($url)
    {
        try{
            $this->getVideoId($url);
            return self::VIDEO_URL;
        } catch (\Exception $e){}

        try{
            $this->getChannelId($url);
            return self::CHANNEL_URL;
        } catch (\Exception $e){}

        try{
            $this->getPlaylistId($url);
            return self::PLAYLIST_URL;
        } catch (\Exception $e){}

        throw new UrlNotValidException($url);
    }

    /**
     * @param $url
     * @return array
     * @throws UrlNotValidException
     */
    public function getVideoId($url)
    {
        $parts = parse_url($url);

        if (isset($parts['path'])) {

            $path = explode('/', trim($parts['path'], '/'));

            if (!empty($path)) {
                if (in_array($path[0], array('channel', 'playlist', 'view_play_list'))) {
                    throw new UrlNotValidException($url);
                }
            }
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $qs);
            if (isset($qs['v'])) {
                return array('id' => $qs['v']);
            } else if (isset($qs['vi'])) {
                return array('id' => $qs['vi']);
            }
        }

        if (isset($parts['path'])) {
            $path = explode('/', trim($parts['path'], '/'));
            if (count($path) >= 2 && in_array($path[0], array('v', 'vi'))) {
                return array('id' => $path[1]);
            }
            if (count($path) === 1) {
                return array('id' => $path[0]);
            }
        }

        throw new UrlNotValidException($url);
    }

    /**
     * Get Youtube channel ID from URL
     *
     * @param string $url
     * @return mixed
     */
    public function getChannelId($url)
    {
        $parts = parse_url($url);

        if (isset($parts['path'])) {

            $path = explode('/', trim($parts['path'], '/'));

            if (count($path) == 2){
                switch($path[0]){
                    case 'channel':
                        return array('id' => $path[1]);
                    case 'user':
                        return array('forUsername' => $path[1]);
                    default:
                        break;
                }
            }
        }

        throw new UrlNotValidException($url);
    }

    /**
     * Get Youtube playlist ID from URL
     *
     * @param string $url
     * @return mixed
     */
    public function getPlaylistId($url)
    {
        $parts = parse_url($url);

        if (isset($parts['path'])) {

            $path = explode('/', trim($parts['path'], '/'));

            if (!empty($path) && in_array($path[0], array('playlist', 'view_play_list'))) {
                if (isset($parts['query'])) {
                    parse_str($parts['query'], $qs);
                    if (isset($qs['list'])) {
                        return array('id' => $qs['list']);
                    }
                    if (isset($qs['p'])) {
                        return array('id' => $qs['p']);
                    }
                }
            }
        }

        throw new UrlNotValidException($url);
    }

    public function cleanURL($url)
    {
        $url = parent::cleanURL($url);
        //TODO: Improve when there is a query "youtube.com/watch/?v=videoid"

        $parts = parse_url($url);
        if (array_key_exists('path',$parts) && $parts['path'] == '/watch') {
            $url = $this->buildVideoURL($parts);
        }
        return $url;
    }

    private function buildVideoURL($parts)
    {
        $parameters = array();
        parse_str($parts['query'], $parameters);
        $parameters = array('v' => $parameters['v']);
        $query = http_build_query($parameters);
        $url = 'https://www.youtube.com/watch?' . $query;
        return $url;
    }
} 