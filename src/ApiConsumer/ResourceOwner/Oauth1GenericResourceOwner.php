<?php

namespace ApiConsumer\ResourceOwner;

use GuzzleHttp\Subscriber\Oauth\Oauth1;

/**
 * Class Oauth1GenericResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class Oauth1GenericResourceOwner extends AbstractResourceOwner
{
    protected $name = 'oauth1';

    /**
     * { @inheritdoc }
     */
    protected function getAuthorizedRequest ($url, array $query = array(), array $token = array())
    {
        $oauth = new Oauth1(
            [
                'consumer_key'    => $this->options['consumer_key'],
                'consumer_secret' => $this->options['consumer_secret'],
                'token'           => $token['oauthToken'],
                'token_secret'    => $token['oauthTokenSecret']
            ]
        );
        $this->httpClient->getEmitter()->attach($oauth);

        $clientConfig = array(
            'query' => $query,
            'auth' => 'oauth',
        );

        return $this->httpClient->createRequest('GET', $url, $clientConfig);
    }
}
