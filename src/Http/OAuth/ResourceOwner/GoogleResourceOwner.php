<?php

namespace Http\OAuth\ResourceOwner;

use ApiConsumer\LinkProcessor\LinkAnalyzer;
use Model\User\TokensModel;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class GoogleResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class GoogleResourceOwner extends Oauth2GenericResourceOwner
{
    protected $name = TokensModel::GOOGLE;

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'base_url' => 'https://www.googleapis.com/',
        ));
    }

    public function refreshAccessToken($token, array $extraParameters = array())
    {
        $refreshToken = $token['refreshToken'];
        $url = 'https://accounts.google.com/o/oauth2/token';
        $parameters = array(
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
            'client_id' => $this->options['consumer_key'],
            'client_secret' => $this->options['consumer_secret'],
        );

        $response = $this->httpClient->post($url, array('body' => $parameters));
        $data = $response->json();

        return $data;
    }

    public function getAPIRequest($url, array $query = array(), array $token = array())
    {

        if (array_key_exists('network', $token) && $token['network'] == LinkAnalyzer::YOUTUBE) {

            $token = $this->getClientToken();

            $clientConfig = array(
                'query' => $query,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token
                )
            );

        } else {
            $query['key'] = $this->clientCredential->getApplicationToken();
            $clientConfig = array(
                'query' => $query,
            );
        }

        return $this->httpClient->createRequest('GET', $url, $clientConfig);
    }
}
