<?php

namespace ApiConsumer\LinkProcessor\Processor;

use Http\OAuth\ResourceOwner\GoogleResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class YoutubeProcessor implements ProcessorInterface
{

    /**
     * @var GoogleResourceOwner
     */
    protected $resourceOwner;

    public function __construct(GoogleResourceOwner $resourceOwner, YoutubeUrlParser $parser)
    {
        $this->resourceOwner = $resourceOwner;
        $this->parser = $parser;
    }

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {

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

        $link['tags'] = array();

        if (isset($response['items']) && is_array($response['items']) && count($response['items']) > 0) {

            $items = $response['items'];
            $info = $items[0];
            $link['title'] = $info['snippet']['title'];
            $link['description'] = $info['snippet']['description'];
            $link['thumbnail'] = 'http://img.youtube.com/vi/' . $id . '/mqdefault.jpg';
            $link['additionalLabels'] = array('Video');
            $link['additionalFields'] = array(
                'embed_type' => 'youtube',
                'embed_id' => $id);
            if (isset($info['topicDetails']['topicIds'])) {
                foreach ($info['topicDetails']['topicIds'] as $tagName) {
                    $link['tags'][] = array(
                        'name' => $tagName,
                        'additionalLabels' => array('Freebase'),
                    );
                }
            }
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

        $link['tags'] = array();

        if (isset($response['items']) && is_array($response['items']) && count($response['items']) > 0) {

            $items = $response['items'];
            $info = $items[0];
            $link['title'] = $info['snippet']['title'];
            $link['description'] = $info['snippet']['description'];
            if (isset($info['brandingSettings']['channel']['keywords'])) {
                $tags = $info['brandingSettings']['channel']['keywords'];
                preg_match_all('/".*?"|\w+/', $tags, $results);
                if ($results) {
                    foreach ($results[0] as $tagName) {
                        $link['tags'][] = array(
                            'name' => $tagName,
                        );
                    }
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

        $link['tags'] = array();

        if (isset($response['items']) && is_array($response['items']) && count($response['items']) > 0) {

            $items = $response['items'];
            $info = $items[0];
            $link['title'] = $info['snippet']['title'];
            $link['description'] = $info['snippet']['description'];
            $link['additionalLabels'] = array('Video');
            $link['additionalFields'] = array(
                'embed_type' => 'youtube_playlist',
                'embed_id' => $id);
            if (isset($info['topicDetails']['topicIds'])) {
                foreach ($info['topicDetails']['topicIds'] as $tagName) {
                    $link['tags'][] = array(
                        'name' => $tagName,
                        'aditionalLabels' => array('Freebase'),
                    );
                }
            }
        }

        return $link;
    }

}