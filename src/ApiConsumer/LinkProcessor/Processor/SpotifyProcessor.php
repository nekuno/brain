<?php

namespace ApiConsumer\LinkProcessor\Processor;

use Http\OAuth\ResourceOwner\SpotifyResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use Http\OAuth\ResourceOwner\GoogleResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;

class SpotifyProcessor implements ProcessorInterface
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


    public function __construct(SpotifyResourceOwner $resourceOwner, SpotifyUrlParser $parser, GoogleResourceOwner $googleResourceOwner, YoutubeUrlParser $youtubeUrlParser)
    {
        $this->resourceOwner = $resourceOwner;
        $this->parser = $parser;
        $this->googleResourceOwner = $googleResourceOwner;
        $this->youtubeUrlParser = $youtubeUrlParser;
    }

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {
        $type = $this->parser->getUrlType($link['url']);

        switch ($type) {
            case SpotifyUrlParser::TRACK_URL:
                $link = $this->processTrack($link);
                break;
            case SpotifyUrlParser::ALBUM_URL:
                $link = $this->processAlbum($link);
                break;
            case SpotifyUrlParser::ARTIST_URL:
                $link = $this->processArtist($link);
                break;
            default:
                return false;
                break;
        }

        return $link;
    }

    protected function processTrack($link)
    {
        $id = $this->parser->getSpotifyIdFromUrl($link['url']);

        if (!$id) {
            return false;
        }

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
                    'embed_id' => $track['uri']);
            }

            $link = $this->addYoutubeSynonymousLinks($track['name'], $track['album']['name'], $link, 2);
        }

        return $link;
    }

    protected function processAlbum($link)
    {
        $id = $this->parser->getSpotifyIdFromUrl($link['url']);

        if (!$id) {
            return false;
        }

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
                'embed_id' => $album['uri']);
        } 
        
        return $link;
    }

    protected function processArtist($link)
    {
        $id = $this->parser->getSpotifyIdFromUrl($link['url']);

        if (!$id) {
            return false;
        }

        $urlArtist = 'artists/' . $id;
        $queryArtist = array();
        $artist= $this->resourceOwner->authorizedAPIRequest($urlArtist, $queryArtist);

        if (isset($artist['name']) && isset($artist['genres'])) {
            foreach ($artist['genres'] as $genre) {
                $tag = array();
                $tag['name'] = $genre;
                $tag['additionalLabels'][] = 'MusicalGenre';
                $link['tags'][] = $tag;
            }

            $tag = array();
            $tag['name'] = $artist['name'];
            $tag['additionalLabels'][] = 'Artist';
            $tag['additionalFields']['spotifyId'] = $artist['id'];
            $link['tags'][] = $tag;
            
            $link['title'] = $artist['name'];
        } 
        
        return $link;
    }

    protected function isYoutubeLinkSynonymous($link, $youtubeLinkSnippetInfo)
    {
        if(isset($youtubeLinkSnippetInfo['title']) && isset($link['title']))
        {
            similar_text($youtubeLinkSnippetInfo['title'], $link['title'], $percent);

            if($percent > 50 && isset($youtubeLinkSnippetInfo['description']) && isset($link['description']))
            {
                similar_text($youtubeLinkSnippetInfo['description'], $link['title'], $percent);
                if($percent > 20)
                {
                    return true;
                }
            }
        }
        return false;
    }

    protected function addYoutubeSynonymousLinks($song, $artists, $link, $numLinks = 1)
    {
        $artists_trimmed = str_replace(' ', '+', $artists);
        $artists_ready = str_replace(',', '+', $artists_trimmed);
        $song_ready = str_replace(' ', '+', $song);
        $queryString = $artists_ready . '+-+' . $song_ready;

        $url = 'youtube/v3/search';
        $query = array(
            'part' => 'snippet,statistics,topicDetails',
            'q' => $queryString,
        );
        $response = $this->googleResourceOwner->authorizedAPIRequest($url, $query);

        if (isset($response['items']) && is_array($response['items']) && count($response['items']) > 0) {

            $items = $response['items'];

            for($i = 0; $i < $numLinks; $i++)
            {
                if(isset($items[$i]))
                {
                    $info = $items[$i];

                    if($this->isYoutubeLinkSynonymous($link, $info['snippet']))
                    {
                        $link['synonymous'] = array();
                        $link['synonymous'][$i] = array();
                        $link['synonymous'][$i]['tags'] = array();
                        $link['synonymous'][$i]['title'] = $info['snippet']['title'];
                        $link['synonymous'][$i]['description'] = $info['snippet']['description'];
                        $link['synonymous'][$i]['additionalLabels'] = array('Video');
                        $link['synonymous'][$i]['additionalFields'] = array(
                            'embed_type' => 'youtube',
                            'embed_id' => $info['id']['videoId']);
                        if (isset($info['topicDetails']['topicIds'])) {
                            foreach ($info['topicDetails']['topicIds'] as $tagName) {
                                $link['synonymous'][$i]['tags'][] = array(
                                    'name' => $tagName,
                                    'additionalLabels' => array('Freebase'),
                                );
                            }
                        }
                    }
                }
            }
        }
        return $link;
    }
}