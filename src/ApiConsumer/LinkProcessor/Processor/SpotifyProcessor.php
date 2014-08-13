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
                    $link['tags'][] = "GENRE: ".$genre;
                }

                foreach ($track['artists'] as $artist) {
                    $link['tags'][] = "ARTIST: ".$artist['name'];
                    $artistList[] = $artist['name'];
                }

                $link['tags'][] = "ALBUM: ".$track['album']['name'];
                $link['tags'][] = "SONG: ".$track['name'];

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
                $link['tags'][] = "GENRE: ".$genre;
            }

            foreach ($album['artists'] as $artist) {
                $link['tags'][] = "ARTIST: ".$artist['name'];
                $artistList[] = $artist['name'];
            }

            $link['tags'][] = "ALBUM: ".$album['name'];

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
                $link['tags'][] = "GENRE: ".$genre;
            }
            $link['tags'][] = "ARTIST: ".$artist['name'];
            
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
        /*
         * TODO 1: Añadir isrc y spotify id
         * TODO 2: Labels propias
        */

        /*
Tracks => Se obtienen Tags de los Genres de su álbum (:MusicalGenre), y se añade Tag por el álbum (:Album), por cada artist (:Artist) y de canción (:Song) añadir ISRC.
Álbum => Se obtienen Tags de los Genres del álbum, y se añade Tag por el álbum y por cada Artist.
Artist => Se añade Tag de Artist, MusicalGenre.
Playlist => Se ignoran y se dejan como enlace por defecto.


        tracks: https://api.spotify.com/v1/tracks/{id}
        album.href -> url api albums
        album.name -> nombre del album
        album.id -> id del album
        artists[].name -> nombre del artista
        artists[].id -> id del artista
        external_ids.isrc -> isrc del track
        (https://developer.spotify.com/web-api/get-track/)

        album: https://api.spotify.com/v1/albums/{id}
        genres[] -> generos
        artists[].name -> nombre del artista
        artists[].id -> id del artista
        id -> id del album
        name -> nombre del album
        (https://developer.spotify.com/web-api/get-album/)

        artist: https://api.spotify.com/v1/artists/
        {id}
        id -> id del artista
        name -> nombre del artista
        genres[] -> generos
        */
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
        }

        return $link;
    }
}