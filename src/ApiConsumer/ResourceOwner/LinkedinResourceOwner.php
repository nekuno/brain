<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\Event\OAuthTokenEvent;
use ApiConsumer\Exception\TokenException;
use GuzzleHttp\Exception\RequestException;
use Model\Token\Token;
use Symfony\Component\OptionsResolver\OptionsResolver;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\LinkedinResourceOwner as LinkedinResourceOwnerBase;

class LinkedinResourceOwner extends LinkedinResourceOwnerBase
{
    use AbstractResourceOwnerTrait {
        AbstractResourceOwnerTrait::configureOptions as traitConfigureOptions;
        AbstractResourceOwnerTrait::__construct as private traitConstructor;
    }

    public function __construct($httpClient, $httpUtils, $options, $name, $storage, $dispatcher)
    {
        $this->traitConstructor($httpClient, $httpUtils, $options, $name, $storage, $dispatcher);
    }

    public function canRequestAsClient()
    {
        return false;
    }

    protected function sendAuthorizedRequest($url, array $query = array(), Token $token = null)
    {
        $query += $this->getOauthToken($token);

        return $this->httpRequest($this->normalizeUrl($url, $query));
    }

    protected function getOauthToken(Token $token)
    {
        $oauthToken = $token->getOauthToken();
        if (!$oauthToken) {
            throw new TokenException('Oauth token not found');
        }

        return $oauthToken;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $this->traitConfigureOptions($resolver);
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            array(
                'base_url' => 'https://api.linkedin.com/v1/',
                'infos_url' => 'https://api.linkedin.com/v1/people/~:(id,industry,skills,languages)?format=json',
            )
        );

        $resolver->setDefined('redirect_uri');
    }

    public function refreshAccessToken($token, array $extraParameters = array())
    {
        $getCodeURL = 'https://www.linkedin.com/oauth/v2/authorization';
        $query = array(
            'response_type' => 'code',
            'client_id' => $this->getOption('consumer_key'),
            'state' => md5($token['resourceId']),
            'scope' => 'r_basicprofile',
            'redirect_uri' => $this->getOption('redirect_uri'),
        );
        try {
            $response = $this->httpRequest($this->normalizeUrl($getCodeURL, $query));
        } catch (RequestException $e) {
            throw $e;
        }

        $getAccessURL = 'https://www.linkedin.com/oauth/v2/accessToken';
        $content = $this->getResponseContent($response);

        // TODO: Check state field

        if (!isset($content['code'])) {
            throw new \Exception(sprintf('Unable to refresh token: "%s"', $content['error_description']));
        }
        $query = array(
            'code' => $content['code'],
            'grant_type' => 'authorization_code',
            'client_id' => $this->getOption('consumer_key'),
            'client_secret' => $this->getOption('consumer_secret'),
            'redirect_uri' => $this->getOption('redirect_uri')
        );

        $response = $this->httpRequest($this->normalizeUrl($getAccessURL, $query));

        return $this->getResponseContent($response);
    }

    public function forceRefreshAccessToken(Token $token)
    {
        $data = $this->refreshAccessToken($token->toArray());
        $this->addOauthData($data, $token);
        $event = new OAuthTokenEvent($token);
        $this->dispatcher->dispatch(\AppEvents::TOKEN_REFRESHED, $event);

        return $token;
    }
}
