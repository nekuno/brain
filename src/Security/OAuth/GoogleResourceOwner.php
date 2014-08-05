<?php

namespace Security\OAuth;

use ApiConsumer\Auth\UserProviderInterface;
use GuzzleHttp\Client;

/**
 * Class GoogleResourceOwner
 * @package Security\OAuth
 */
class GoogleResourceOwner
{

    /**
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * @var array
     */
    private $options;

    /**
     * @var \ApiConsumer\Auth\UserProviderInterface
     */
    private $userProvider;

    /**
     * @param Client $httpClient
     * @param UserProviderInterface $userProvider
     * @param array $options
     */
    public function __construct(Client $httpClient, UserProviderInterface $userProvider, array $options = array())
    {
        $this->httpClient = $httpClient;
        $this->userProvider = $userProvider;
        $this->options = $options;
    }

    /**
     * Refresh Access Token
     *
     * @param $user
     * @return string newAccessToken
     */
    public function refreshAccessToken($user)
    {
        $url = 'https://accounts.google.com/o/oauth2/token';
        $parameters = array(
            'refresh_token' => $user['refreshToken'],
            'grant_type' => 'refresh_token',
            'client_id' => $this->options['consumer_key'],
            'client_secret' => $this->options['consumer_secret'],
        );

        $response = $this->httpClient->post($url, array('body' => $parameters));
        $data = $response->json();

        $userId = $user['id'];
        $accessToken = $data['access_token'];
        $creationTime = time();
        $expirationTime = time() + $data['expires_in'];
        $this->userProvider->updateAccessToken('google', $userId, $accessToken, $creationTime, $expirationTime);

        return $accessToken;
    }


}
