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

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {
        /*
         * TODO: 1 Decidir el tipo de enlace
         * TODO: 2 Extraer los datos necesarios del enlace
         * TODO: 3 Llamar a la API
         * TODO: 4 Procesar la respuesta
         * TODO: 5 Extraer la información
         * TODO: 6 Devolver el enlace procesado
        */

        /*
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

        $id = '7DhnBXTRbyW3JRueLlOmLm';
        $url = 'tracks/' . $id;
        $query = array();
        $response = $this->resourceOwner->authorizedAPIRequest($url, $query);
        var_dump($response);

        $link['tags'] = array();
        $link['title'] = '';
        $link['description'] = '';

        return $link;
    }
}