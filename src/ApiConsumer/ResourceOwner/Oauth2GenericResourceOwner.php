<?php

namespace ApiConsumer\ResourceOwner;

/**
 * Class Oauth1GenericResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
trait Oauth2GenericResourceOwner
{
    protected $name;
	protected $httpClient;

    /**
     * { @inheritdoc }
     */
    protected function getAuthorizedRequest($url, array $query = array(), array $token = array())
    {
        $query = array_merge($query, array('access_token' => $token['oauthToken']));

        $clientConfig = array(
            'query' => $query,
        );

        return $this->httpClient->createRequest('GET', $url, $clientConfig);
    }
}
