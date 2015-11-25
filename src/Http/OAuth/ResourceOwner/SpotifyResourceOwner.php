<?php

namespace Http\OAuth\ResourceOwner;

use Model\User\TokensModel;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FacebookResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class SpotifyResourceOwner extends Oauth2GenericResourceOwner
{
    protected $name = TokensModel::SPOTIFY;

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'base_url' => 'https://api.spotify.com/v1/',
        ));
    }

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

    public function refreshAccessToken($token, array $extraParameters = array())
    {
        $refreshToken = $token['refreshToken'];
        $url = 'https://accounts.spotify.com/api/token';
        $authorization = base64_encode($this->options['consumer_key'] . ":" . $this->options['consumer_secret']);
        $headers = array('Authorization' => 'Basic ' . $authorization);
        $body = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        );

        $response = $this->httpClient->post($url, array('headers' => $headers, 'body' => $body));
        $data = $response->json();

        return $data;
    }

    protected function addOauthData($data, $token)
    {
        $newToken = parent::addOauthData($data, $token);
        if (!isset($newToken['refreshToken']) && isset($token['refreshToken'])){
            $newToken['refreshToken'] = $token['refreshToken'];
        }
        return $newToken;
    }

    public function getAPIRequest($url, array $query = array(), array $token = array())
    {
        // Spotify seems to allow anonymous calls
        // $query = array_merge($query, array('key' => $this->options['api_key']));

        $clientConfig = array(
            'query' => $query,
        );

        return $this->httpClient->createRequest('GET', $url, $clientConfig);
    }
}
