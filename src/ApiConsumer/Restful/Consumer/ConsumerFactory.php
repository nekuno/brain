<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 8/07/14
 * Time: 18:13
 */

namespace ApiConsumer\Restful\Consumer;

use GuzzleHttp\Client;
use ApiConsumer\Auth\UserProviderInterface;

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
                $consumer = new TwitterLinksConsumer($userProvider, $httpClient, $options);
                break;
            case 'facebook':
                $consumer = new FacebookLinksConsumer($userProvider, $httpClient);
                break;
            case 'google':
                $consumer = new GoogleLinksConsumer($userProvider, $httpClient);
                break;
            default:
                throw new \Exception('Invalid consumer');
        }
        return $consumer;

    }

}
