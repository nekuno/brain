<?php

namespace ApiConsumer\LinkProcessor\Processor;

use Http\OAuth\ResourceOwner\SpotifyResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;

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

    public function __construct(SpotifyResourceOwner $resourceOwner, SpotifyUrlParser $parser)
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
        $querytrack = array();
        $track = $this->resourceOwner->authorizedAPIRequest($urlTrack, $querytrack);

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
                $link['additionalLabels'] = array('Audio');
                $link['additionalFields'] = array(
                    'embed_type' => 'spotify',
                    'embed_id' => $track['uri']);
            }
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
}