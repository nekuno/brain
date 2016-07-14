<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\ResourceOwner\GoogleResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class YoutubeProcessor extends AbstractProcessor
{
    /**
     * @var GoogleResourceOwner
     */
    protected $resourceOwner;

    /**
     * @var YoutubeUrlParser
     */
    protected $parser;

    /**
     * @inheritdoc
     */
    public function process(PreprocessedLink $preprocessedLink)
    {
        $type = $this->parser->getUrlType($preprocessedLink->getCanonical());

        $link = $preprocessedLink->getLink();
        $link['url'] = $preprocessedLink->getCanonical();
        $preprocessedLink->setLink($link);

        switch ($type) {
            case YoutubeUrlParser::VIDEO_URL:
                $link = $this->processVideo($preprocessedLink);
                break;
            case YoutubeUrlParser::CHANNEL_URL:
                $link = $this->processChannel($preprocessedLink);
                break;
            case YoutubeUrlParser::PLAYLIST_URL:
                $link = $this->processPlaylist($preprocessedLink);
                break;
            default:
                $link = $this->scraperProcessor->process($preprocessedLink);
                break;
        }

        $link['url'] = $preprocessedLink->getCanonical();
        return $link;
    }

    protected function processVideo(PreprocessedLink $preprocessedLink)
    {

        $id = $this->parser->getYoutubeIdFromUrl($preprocessedLink->getCanonical());

        $url = 'youtube/v3/videos';
        $query = array(
            'part' => 'snippet,statistics,topicDetails',
            'id' => $id,
        );
        $token = array('network' => LinkAnalyzer::YOUTUBE);
        $response = $this->resourceOwner->authorizedAPIRequest($url, $query, $token);

        $link = $preprocessedLink->getLink();
        $link['tags'] = array();

        if (isset($response['items']) && is_array($response['items']) && count($response['items']) > 0) {

            $items = $response['items'];
            $info = $items[0];
            $link['title'] = $info['snippet']['title'];
            $link['description'] = $info['snippet']['description'];
            $link['thumbnail'] = 'https://img.youtube.com/vi/' . $id . '/mqdefault.jpg';
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
        } else {
            //TODO: Use exceptions logic here
            //YouTube API returns 200 on non-existent videos, against its documentation
            //$response = $this->resourceOwner->authorizedAPIRequest($this->resourceOwner->getOption('base_url').$url,$query, $token);
            //throw new RequestException('Video does not exist', $request, null, null);
            $preprocessedLink->addToLink(array('processed' => 0));
        }

        return $link;
    }

    protected function processChannel(PreprocessedLink $preprocessedLink)
    {

        $id = $this->parser->getChannelIdFromUrl($preprocessedLink->getCanonical());

        $url = 'youtube/v3/channels';
        $query = array(
            'part' => 'snippet,brandingSettings,contentDetails,invideoPromotion,statistics,topicDetails',
            'id' => $id,
        );
        $response = $this->resourceOwner->authorizedAPIRequest($url, $query);

        $link = $preprocessedLink->getLink();

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

    protected function processPlaylist(PreprocessedLink $preprocessedLink)
    {

        $id = $this->parser->getPlaylistIdFromUrl($preprocessedLink->getCanonical());

        $url = 'youtube/v3/playlists';
        $query = array(
            'part' => 'snippet,status',
            'id' => $id,
        );
        $response = $this->resourceOwner->authorizedAPIRequest($url, $query);

        $link = $preprocessedLink->getLink();

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
                        'additionalLabels' => array('Freebase'),
                    );
                }
            }
        }

        return $link;
    }

}