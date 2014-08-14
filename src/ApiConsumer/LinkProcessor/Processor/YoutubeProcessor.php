<?php

namespace ApiConsumer\LinkProcessor\Processor;

use Http\OAuth\ResourceOwner\GoogleResourceOwner;
use ApiConsumer\LinkProcessor\Parser\YoutubeUrlParser;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class YoutubeProcessor implements ProcessorInterface
{

    /**
     * @var GoogleResourceOwner
     */
    protected $resourceOwner;

    public function __construct(GoogleResourceOwner $resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
        $this->parser = new YoutubeUrlParser();
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

        $type = $this->parser->getUrlType($link['url']);

        switch ($type) {
            case YoutubeUrlParser::VIDEO_URL:
                $link = $this->processVideo($link);
                break;
            case YoutubeUrlParser::CHANNEL_URL:
                $link = $this->processChannel($link);
                break;
            case YoutubeUrlParser::PLAYLIST_URL:
                $link = $this->processPlaylist($link);
                break;
            default:
                return false;
                break;
        }

        return $link;
    }

    protected function processVideo($link)
    {

        $id = $this->parser->getYoutubeIdFromUrl($link['url']);

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

        $id = $this->parser->getChannelIdFromUrl($link['url']);

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

    protected function processPlaylist($link)
    {

        $id = $this->parser->getPlaylistIdFromUrl($link['url']);

        $url = 'youtube/v3/playlists';
        $query = array(
            'part' => 'snippet,status',
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

}