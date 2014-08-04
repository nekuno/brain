<?php


namespace Security\OAuth;


use ApiConsumer\Auth\UserProviderInterface;
use GuzzleHttp\Client;

/**
 * Class SpotifyResourceOwner
 * @package Security\OAuth
 */
class SpotifyResourceOwner
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
        $url = "https://accounts.spotify.com/api/token?grant_type=refresh_token&refresh_token=" . $user['refreshToken'];
        $authorization = base64_encode($this->options['consumer_key'] . ":" . $this->options['consumer_secret']);
        $headers = array('Authorization' => 'Basic ' . $authorization);

        $response = $this->httpClient->post($url, array('headers' => $headers));
        $data = $response->json();

        $userId = $user['id'];
        $accessToken = $data['access_token'];
        $creationTime = time();
        $expirationTime = time() + $data['expires_in'];
        $this->userProvider->updateAccessToken('spotify', $userId, $accessToken, $creationTime, $expirationTime);

        return $accessToken;
    }

}
