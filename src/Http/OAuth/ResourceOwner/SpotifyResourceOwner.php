<?php

namespace Http\OAuth\ResourceOwner;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FacebookResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class SpotifyResourceOwner extends Oauth2GenericResourceOwner
{
    protected $name = 'spotify';

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'base_url' => 'https://api.spotify.com/v1/',
        ));
    }

    /**
     * { @inheritdoc }
     */
    protected function getAuthorizedRequest ($url, array $query = array(), array $token = array())
    {
        $query = array_merge($query, array('access_token' => $token['oauthToken']));

        $headers = array('Authorization' => 'Bearer ' . $token['oauthToken']);

        $clientConfig = array(
            'query'   => $query,
            'headers' => $headers,
        );

        return $this->httpClient->createRequest('GET', $url, $clientConfig);
    }

    public function refreshAccessToken($refreshToken, array $extraParameters = array())
    {
        $url = 'https://accounts.spotify.com/api/token?grant_type=refresh_token&refresh_token=' . $refreshToken;
        $authorization = base64_encode($this->options['consumer_key'] . ":" . $this->options['consumer_secret']);
        $headers = array('Authorization' => 'Basic ' . $authorization);

        $response = $this->httpClient->post($url, array('headers' => $headers));
        $data = $response->json();

        return $data;
    }
}
