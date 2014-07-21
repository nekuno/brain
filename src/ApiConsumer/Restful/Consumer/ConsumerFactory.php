<?php

namespace ApiConsumer\Restful\Consumer;

use ApiConsumer\Auth\UserProviderInterface;
use GuzzleHttp\Client;

class ConsumerFactory
{

    /**
     * @param $resource
     * @param UserProviderInterface $userProvider
     * @param Client $httpClient
     * @param array $options
     * @return LinksConsumerInterface
     * @throws \Exception
     */
    public static function create(
        $resource,
        UserProviderInterface $userProvider,
        Client $httpClient,
        array $options = array()
    ) {

        switch ($resource) {
            case 'twitter':
                $consumer = new TwitterConsumer($userProvider, $httpClient, $options);
                break;
            case 'facebook':
                $consumer = new FacebookConsumer($userProvider, $httpClient);
                break;
            case 'google':
                $consumer = new GoogleConsumer($userProvider, $httpClient);
                break;
            case 'spotify':
                $consumer = new SpotifyConsumer($userProvider, $httpClient);
                break;
            default:
                throw new \Exception('Invalid consumer name given');
        }
        return $consumer;
    }
}
