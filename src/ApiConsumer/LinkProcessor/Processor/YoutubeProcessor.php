<?php

namespace ApiConsumer\LinkProcessor\Processor;

use Http\OAuth\ResourceOwner\GoogleResourceOwner;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class YoutubeProcessor implements ProcessorInterface
{

    const VIDEO_URL = 'video';
    const CHANNEL_URL = 'channel';

    /**
     * @var GoogleResourceOwner
     */
    protected $resourceOwner;

    public function __construct(GoogleResourceOwner $resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
    }

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {
        /*
         * TODO: Extract tags from freebase (topicIds)
        */

        $type = $this->getUrlType($link['url']);

        switch ($type) {
            case self::VIDEO_URL:
                $link = $this->processVideo($link);
                break;
            case self::CHANNEL_URL:
                $link = $this->processChannel($link);
                break;
            default:
                return false;
                break;
        }

        return $link;
    }

    protected function processVideo($link)
    {

        $id = $this->getYoutubeIdFromUrl($link['url']);

        $url = 'youtube/v3/videos';
        $query = array(
            'part' => 'snippet,statistics,topicDetails',
            'id' => $id,
        );
        $response = $this->resourceOwner->authorizedAPIRequest($url, $query);

        $items = $response['items'];

        $link['tags'] = array();

        if ($items) {
            $info = $items[0];
            $link['title'] = $info['snippet']['title'];
            $link['description'] = $info['snippet']['description'];
        }

        return $link;
    }

    protected function processChannel($link)
    {

        $id = $this->getChannelIdFromUrl($link['url']);

        $url = 'youtube/v3/channels';
        $query = array(
            'part' => 'snippet,brandingSettings,contentDetails,invideoPromotion,statistics,topicDetails',
            'id' => $id,
        );
        $response = $this->resourceOwner->authorizedAPIRequest($url, $query);

        $items = $response['items'];

        $link['tags'] = array();

        if ($items) {
            $info = $items[0];
            $link['title'] = $info['snippet']['title'];
            $link['description'] = $info['snippet']['description'];

            if (isset($info['brandingSettings']['channel']['keywords'])) {
                $tags = $info['brandingSettings']['channel']['keywords'];
                preg_match_all('/".*?"|\w+/', $tags, $results);
                if ($results) {
                    $link['tags'] = $results[0];
                }
            }
        }

        return $link;
    }

    protected function getUrlType($url)
    {
        if ($this->getYoutubeIdFromUrl($url)) {
            return self::VIDEO_URL;
        }

        if ($this->getChannelIdFromUrl($url)) {
            return self::CHANNEL_URL;
        }

        return false;
    }

    /**
     * Get Youtube video ID from URL
     *
     * @param string $url
     * @return mixed Youtube video ID or FALSE if not found
     */
    protected function getYoutubeIdFromUrl($url)
    {

        $parts = parse_url($url);

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
    protected function getChannelIdFromUrl($url)
    {

        $parts = parse_url($url);

        $path = explode('/', trim($parts['path'], '/'));
        if (!empty($path) && $path[0] === 'channel' && $path[1]) {
            return $path[1];
        }

        return false;
    }
}