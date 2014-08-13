<?php

namespace ApiConsumer\LinkProcessor\Processor;

use Http\OAuth\ResourceOwner\GoogleResourceOwner;

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
    }

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {
        /*
         * TODO: 1 Decidir el tipo de enlace, de video o de canal
         * TODO: 2 Extraer los datos necesarios del enlace
         * TODO: 3 Llamar a la API
         * TODO: 4 Procesar la respuesta
         * TODO: 5 Extraer la información
         * TODO: 6 Devolver el enlace procesado
        */

        $id = 'zLgY05beCnY';
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
}