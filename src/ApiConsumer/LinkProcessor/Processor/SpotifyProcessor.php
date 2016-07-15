<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\ResourceOwner\SpotifyResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use ApiConsumer\ResourceOwner\GoogleResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use GuzzleHttp\Client;
use Service\UserAggregator;

class SpotifyProcessor extends AbstractProcessor
{
    /**
     * @var SpotifyResourceOwner
     */
    protected $resourceOwner;

    /**
     * @var SpotifyUrlParser
     */
    protected $parser;

    /**
     * @var GoogleResourceOwner
     */
    protected $googleResourceOwner;

    /**
     * @var YoutubeUrlParser
     */
    protected $youtubeUrlParser;

    public function __construct(UserAggregator $userAggregator, ScraperProcessor $scraperProcessor, SpotifyResourceOwner $resourceOwner, SpotifyUrlParser $parser, GoogleResourceOwner $googleResourceOwner, YoutubeUrlParser $youtubeUrlParser, Client $client)
    {
	    $this->userAggregator = $userAggregator;
	    $this->scraperProcessor = $scraperProcessor;
	    $this->resourceOwner = $resourceOwner;
	    $this->parser = $parser;
        $this->googleResourceOwner = $googleResourceOwner;
        $this->youtubeUrlParser = $youtubeUrlParser;
	    $this->client = $client;
    }

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
            case SpotifyUrlParser::TRACK_URL:
            case SpotifyUrlParser::ALBUM_TRACK_URL:
                $link = $this->processTrack($preprocessedLink);
                break;
            case SpotifyUrlParser::ALBUM_URL:
                $link = $this->processAlbum($preprocessedLink);
                break;
            case SpotifyUrlParser::ARTIST_URL:
                $link = $this->processArtist($preprocessedLink);
                break;
            default:
                $link = $this->scraperProcessor->process($preprocessedLink);
                break;
        }

        return $link;
    }

    protected function processTrack(PreprocessedLink $preprocessedLink)
    {
        $id = $this->parser->getSpotifyIdFromUrl($preprocessedLink->getCanonical());

        if (!$id) {
            return false;
        }

        $link = $preprocessedLink->getLink();

        $urlTrack = 'tracks/' . $id;
        $queryTrack = array();
        $track = $this->resourceOwner->authorizedAPIRequest($urlTrack, $queryTrack);

        if (isset($track['name']) && isset($track['album']) && isset($track['artists'])) {
            $urlAlbum = 'albums/' . $track['album']['id'];
            $queryAlbum = array();
            $album = $this->resourceOwner->authorizedAPIRequest($urlAlbum, $queryAlbum);

            if (isset($album['genres'])) {
                foreach ($album['genres'] as $genre) {
                    $tag = array();
                    $tag['name'] = $genre;
                    $tag['additionalLabels'][] = 'MusicalGenre';
                    $link['tags'][] = $tag;
                }
            }

            $artistList = array();
            foreach ($track['artists'] as $artist) {
                $tag = array();
                $tag['name'] = $artist['name'];
                $tag['additionalLabels'][] = 'Artist';
                $tag['additionalFields']['spotifyId'] = $artist['id'];
                $link['tags'][] = $tag;

                $artistList[] = $artist['name'];
            }

            $tag = array();
            $tag['name'] = $track['album']['name'];
            $tag['additionalLabels'][] = 'Album';
            $tag['additionalFields']['spotifyId'] = $track['album']['id'];
            $link['tags'][] = $tag;

            $tag = array();
            $tag['name'] = $track['name'];
            $tag['additionalLabels'][] = 'Song';
            $tag['additionalFields']['spotifyId'] = $track['id'];
            if (isset($track['external_ids']['isrc'])) {
                $tag['additionalFields']['isrc'] = $track['external_ids']['isrc'];
            }
            $link['tags'][] = $tag;

            $link['title'] = $track['name'];
            $link['description'] = $track['album']['name'] . ' : ' . implode(', ', $artistList);
            $link['thumbnail'] = isset($track['album']['images'][1]['url']) ? $track['album']['images'][1]['url'] : null;
            $link['additionalLabels'] = array('Audio');
            $link['additionalFields'] = array(
                'embed_type' => 'spotify',
                'embed_id' => $track['uri']
            );

            $link = $this->addYoutubeSynonymousLinks($track['name'], $artistList, $link);
        }

        return $link;
    }

    protected function processAlbum(PreprocessedLink $preprocessedLink)
    {
        $id = $this->parser->getSpotifyIdFromUrl($preprocessedLink->getCanonical());

        if (!$id) {
            return false;
        }

        $link = $preprocessedLink->getLink();

        $urlAlbum = 'albums/' . $id;
        $queryAlbum = array();
        $album = $this->resourceOwner->authorizedAPIRequest($urlAlbum, $queryAlbum);

        if (isset($album['name']) && isset($album['genres']) && isset($album['artists'])) {
            foreach ($album['genres'] as $genre) {
                $tag = array();
                $tag['name'] = $genre;
                $tag['additionalLabels'][] = 'MusicalGenre';
                $link['tags'][] = $tag;
            }

            $artistList = array();
            foreach ($album['artists'] as $artist) {
                $tag = array();
                $tag['name'] = $artist['name'];
                $tag['additionalLabels'][] = 'Artist';
                $tag['additionalFields']['spotifyId'] = $artist['id'];
                $link['tags'][] = $tag;

                $artistList[] = $artist['name'];
            }

            $tag = array();
            $tag['name'] = $album['name'];
            $tag['additionalLabels'][] = 'Album';
            $tag['additionalFields']['spotifyId'] = $album['id'];
            $link['tags'][] = $tag;

            $link['title'] = $album['name'];
            $link['description'] = 'By: ' . implode(', ', $artistList);
            $link['additionalLabels'] = array('Audio');
            $link['additionalFields'] = array(
                'embed_type' => 'spotify',
                'embed_id' => $album['uri']
            );
        }

        return $link;
    }

    protected function processArtist(PreprocessedLink $preprocessedLink)
    {
        $id = $this->parser->getSpotifyIdFromUrl($preprocessedLink->getCanonical());

        if (!$id) {
            return false;
        }

        $link = $preprocessedLink->getLink();

        $urlArtist = 'artists/' . $id;
        $queryArtist = array();
        $artist = $this->resourceOwner->authorizedAPIRequest($urlArtist, $queryArtist);

        if (isset($artist['name']) && isset($artist['genres'])) {
            foreach ($artist['genres'] as $genre) {
                $tag = array();
                $tag['name'] = $genre;
                $tag['additionalLabels'][] = 'MusicalGenre';
                $link['tags'][] = $tag;
            }

            //TODO: Check consistency of this with channels
            $tag = array();
            $tag['name'] = $artist['name'];
            $tag['additionalLabels'][] = 'Artist';
            $tag['additionalFields']['spotifyId'] = $artist['id'];
            $link['tags'][] = $tag;

            $link['title'] = $artist['name'];
            //TODO: Add description
        }

        return $link;
    }

    protected function isYoutubeLinkSynonymous($link, $youtubeLinkSnippetInfo)
    {
        if (isset($youtubeLinkSnippetInfo['title']) && isset($link['title'])) {

            similar_text($youtubeLinkSnippetInfo['title'], $link['title'], $percent);

            if ($percent > 30) {
                return true;
            }
        }

        return false;
    }

    protected function addYoutubeSynonymousLinks($song, $artists, $link, $numLinks = 3)
    {

        $queryString = implode(', ', $artists) . ' ' . $song;

        $url = 'youtube/v3/search';
        $query = array(
            'q' => $queryString,
            'part' => 'snippet',
            'type' => 'video'
        );
        $token = array('network' => LinkAnalyzer::YOUTUBE);
        $response = $this->googleResourceOwner->authorizedAPIRequest($url, $query, $token);

        if (isset($response['items']) && is_array($response['items']) && count($response['items']) > 0) {

            $items = $response['items'];

            $link['synonymous'] = array();

            for ($i = 0; $i < $numLinks; $i++) {
                if (isset($items[$i])) {
                    $info = $items[$i];

                    if ($this->isYoutubeLinkSynonymous($link, $info['snippet'])) {

                        $synonymous = array();
                        $synonymous['url'] = 'https://www.youtube.com/watch?v=' . $info['id']['videoId'];
                        $synonymous['tags'] = array();
                        $synonymous['title'] = $info['snippet']['title'];
                        $synonymous['description'] = $info['snippet']['description'];
                        $synonymous['thumbnail'] = 'https://img.youtube.com/vi/' . $info['id']['videoId'] . '/mqdefault.jpg';
                        $synonymous['additionalLabels'] = array('Video');
                        $synonymous['additionalFields'] = array(
                            'embed_type' => 'youtube',
                            'embed_id' => $info['id']['videoId']
                        );

                        if (isset($info['topicDetails']['topicIds'])) {
                            foreach ($info['topicDetails']['topicIds'] as $tagName) {
                                $synonymous['tags'][] = array(
                                    'name' => $tagName,
                                    'additionalLabels' => array('Freebase'),
                                );
                            }
                        }

                        $link['synonymous'][] = $synonymous;
                    }
                }
            }
        }

        return $link;
    }
}