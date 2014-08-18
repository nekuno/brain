<?php

namespace ApiConsumer\LinkProcessor\Processor;

use Http\OAuth\ResourceOwner\SpotifyResourceOwner;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class SpotifyProcessor implements ProcessorInterface
{

    /**
     * @var SpotifyResourceOwner
     */
    protected $resourceOwner;

    public function __construct(SpotifyResourceOwner $resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
    }

    protected function processTrack($link, $id)
    {
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
                    $tag['aditionalLabels'][] = 'MusicalGenre';
                    $link['tags'][] = $tag;
                }

                $artistList = array();
                foreach ($track['artists'] as $artist) {
                    $tag = array();
                    $tag['name'] = $artist['name'];
                    $tag['aditionalLabels'][] = 'Artist';
                    $tag['aditionalFields']['spotifyId'] = $artist['id'];
                    $link['tags'][] = $tag;

                    $artistList[] = $artist['name'];
                }

                $tag = array();
                $tag['name'] = $track['album']['name'];
                $tag['aditionalLabels'][] = 'Album';
                $tag['aditionalFields']['spotifyId'] = $track['album']['id'];
                $link['tags'][] = $tag;

                $tag = array();
                $tag['name'] = $track['name'];
                $tag['aditionalLabels'][] = 'Song';
                $tag['aditionalFields']['spotifyId'] = $track['id'];
                if (isset($track['external_ids']['isrc'])) {
                    $tag['aditionalFields']['isrc'] = $track['external_ids']['isrc'];
                }
                $link['tags'][] = $tag;

                $link['title'] = $track['name'];
                $link['description'] = $track['album']['name'] . ' : ' . implode(', ', $artistList);
            }
        } 

        return $link;
    }

    protected function processAlbum($link, $id)
    {

        $urlAlbum = 'albums/' . $id;
        $queryAlbum = array();
        $album = $this->resourceOwner->authorizedAPIRequest($urlAlbum, $queryAlbum);

        if (isset($album['name']) && isset($album['genres']) && isset($album['artists'])) {
            foreach ($album['genres'] as $genre) {
                $tag = array();
                $tag['name'] = $genre;
                $tag['aditionalLabels'][] = 'MusicalGenre';
                $link['tags'][] = $tag;
            }

            foreach ($album['artists'] as $artist) {
                $tag = array();
                $tag['name'] = $artist['name'];
                $tag['aditionalLabels'][] = 'Artist';
                $tag['aditionalFields']['spotifyId'] = $artist['id'];
                $link['tags'][] = $tag;

                $artistList[] = $artist['name'];
            }
                
            $tag = array();
            $tag['name'] = $album['name'];
            $tag['aditionalLabels'][] = 'Album';
            $tag['aditionalFields']['spotifyId'] = $album['id'];
            $link['tags'][] = $tag;

            $link['title'] = $album['name'];
            $link['description'] = 'By: ' . implode(', ', $artistList);
        } 
        
        return $link;
    }

    protected function processArtist($link, $id)
    {

        $urlArtist = 'artists/' . $id;
        $queryArtist = array();
        $artist= $this->resourceOwner->authorizedAPIRequest($urlArtist, $queryArtist);

        if (isset($artist['name']) && isset($artist['genres'])) {
            foreach ($artist['genres'] as $genre) {
                $tag = array();
                $tag['name'] = $genre;
                $tag['aditionalLabels'][] = 'MusicalGenre';
                $link['tags'][] = $tag;
            }

            $tag = array();
            $tag['name'] = $artist['name'];
            $tag['aditionalLabels'][] = 'Artist';
            $tag['aditionalFields']['spotifyId'] = $artist['id'];
            $link['tags'][] = $tag;
            
            $link['title'] = $artist['name'];
        } 
        
        return $link;
    }

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {
        $kind = 'none';
        $id = '0';

        $parsedUrl = parse_url($link['url']);
        $path = explode('/', $parsedUrl['path']);
        if (count($path) === 3) {
            $kind = $path[1];
            $id = $path[2];
        }

        switch ($kind) {
            case 'track':
                $link = $this->processTrack($link, $id);
                break;
            case 'album':
                $link = $this->processAlbum($link, $id);
                break;
            case 'artist':
                $link = $this->processArtist($link, $id);
                break;
            default:
                $link = FALSE;
                break;
        }

        return $link;
    }
}