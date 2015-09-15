<?php

namespace Http\OAuth\ResourceOwner;

use GuzzleHttp\Exception\RequestException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FacebookResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class
FacebookResourceOwner extends Oauth2GenericResourceOwner
{
    protected $name = 'facebook';

    protected $expire_time_margin = 1728000;// 20 days because expired tokens can´t be refreshed

    protected $redirect_uri = 'https://dev.nekuno.com/login/check-facebook'; //exactly as in facebook app

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'base_url' => 'https://graph.facebook.com/v2.4/',
        ));
    }

    /**
     * We use Facebook system for getting new long-lived tokens
     * and assume machine-id as a non-obligatory refreshToken
     * @param array $token
     * @param array $extraParameters
     * @return array
     */
    public function refreshAccessToken($token, array $extraParameters = array())
    {

        $getCodeURL = 'https://graph.facebook.com/oauth/client_code';
        $query = array(
            'access_token' => $token['oauthToken'],
            'client_id' => $this->getOption('consumer_key'),
            'client_secret' => $this->getOption('consumer_secret'),
            'redirect_uri' => $this->redirect_uri,
        );
        try {
            $request = $this->getAPIRequest($getCodeURL, $query);
            $response = $this->httpClient->send($request);
        } catch (RequestException $e) {
            throw $e;
        }

        $getAccessURL = 'https://graph.facebook.com/oauth/access_token';
        $query = array(
            'code' => $response->json()['code'],
            'client_id' => $this->getOption('consumer_key'),
            'redirect_uri' => $this->redirect_uri,
        );

        if (array_key_exists('refreshToken', $token) && null !== $token['refreshToken']) {
            $query['machine_id'] = $token['refreshToken'];
        }

        $request = $this->getAPIRequest($getAccessURL, $query);
        var_dump($request->getUrl());
        $response = $this->httpClient->send($request);
        $data = $response->json();
        return array_merge($data, array('refreshToken' => $data['machine_id']));

    }

}
