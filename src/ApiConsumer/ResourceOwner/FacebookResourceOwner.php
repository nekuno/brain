<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\Event\OAuthTokenEvent;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use Event\ExceptionEvent;
use GuzzleHttp\Exception\RequestException;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\FacebookResourceOwner as FacebookResourceOwnerBase;
use Model\Token\Token;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FacebookResourceOwner extends FacebookResourceOwnerBase
{
    use AbstractResourceOwnerTrait {
        AbstractResourceOwnerTrait::configureOptions as traitConfigureOptions;
        AbstractResourceOwnerTrait::__construct as private traitConstructor;
    }

    /** @var FacebookUrlParser */
    protected $urlParser;

    public function __construct($httpClient, $httpUtils, $options, $name, $storage, $dispatcher)
    {
        $this->traitConstructor($httpClient, $httpUtils, $options, $name, $storage, $dispatcher);
        $this->expire_time_margin = 1728000; //20 days
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
                'base_url' => 'https://graph.facebook.com/v2.9/',
            )
        );

        $resolver->setDefined('redirect_uri');
    }

    public function canRequestAsClient()
    {
        return true;
    }

    public function requestAsClient($url, array $query = array())
    {
        $url = $this->options['base_url'] . $url;

        $token = $this->getOption('consumer_key'). '|' . $this->getOption('consumer_secret');
        $query += array('access_token' => $token);

        $response = $this->httpRequest($this->normalizeUrl($url, $query));

        return $this->getResponseContent($response);
    }

    /**
     * We use Facebook system for getting new long-lived tokens
     * and assume machine-id as a non-obligatory refreshToken
     * @param array $token
     * @param array $extraParameters
     * @return array
     * @throws \Exception
     */
    public function refreshAccessToken($token, array $extraParameters = array())
    {
        $getCodeURL = 'https://graph.facebook.com/oauth/client_code';
        $query = array(
            'access_token' => $token['oauthToken'],
            'client_id' => $this->getOption('consumer_key'),
            'client_secret' => $this->getOption('consumer_secret'),
            'redirect_uri' => $this->getOption('redirect_uri'),
        );
        try {
            $response = $this->httpRequest($this->normalizeUrl($getCodeURL, $query));
        } catch (RequestException $e) {
            throw $e;
        }

        $getAccessURL = 'https://graph.facebook.com/oauth/access_token';
        $content = $this->getResponseContent($response);
        if (!isset($content['code'])) {
            throw new \Exception(sprintf('Unable to refresh token: "%s"', $content['error']['message']));
        }
        $query = array(
            'code' => $content['code'],
            'client_id' => $this->getOption('consumer_key'),
            'redirect_uri' => $this->getOption('redirect_uri'),
        );

        if (array_key_exists('refreshToken', $token) && null !== $token['refreshToken']) {
            $query['machine_id'] = $token['refreshToken'];
        }

        $response = $this->httpRequest($this->normalizeUrl($getAccessURL, $query));
        $data = $this->getResponseContent($response);

        return array_merge($data, array('refreshToken' => $data['machine_id']));

    }

    public function forceRefreshAccessToken(Token $token)
    {
        $data = $this->refreshAccessToken($token->toArray());
        $this->addOauthData($data, $token);
        $event = new OAuthTokenEvent($token);
        $this->dispatcher->dispatch(\AppEvents::TOKEN_REFRESHED, $event);

        return $token;
    }

    protected function canRefresh($token)
    {
        return true;
    }

    public function extend(Token $token)
    {
        $getCodeURL = 'https://graph.facebook.com/oauth/access_token';
        $query = array(
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->getOption('consumer_key'),
            'client_secret' => $this->getOption('consumer_secret'),
            'fb_exchange_token' => $token->getOauthToken(),
        );

        try {
            $response = $this->httpRequest($this->normalizeUrl($getCodeURL, $query));
            $data = $this->getResponseContent($response);
            if (isset($data['expires'])) {
                $data['expires_in'] = $data['expires'];
                unset($data['expires']);
            }
        } catch (RequestException $e) {
            throw $e;
        }

        $this->addOauthData($data, $token);
    }

    public function requestSmallPicture($id, Token $token = null)
    {
        return $this->requestPicture($id, $token, 'small');
    }

    public function requestLargePicture($id, Token $token = null)
    {
        return $this->requestPicture($id, $token, 'large');
    }

    protected function requestPicture($id, Token $token = null, $size = 'large')
    {
        //TODO: Try to move out of here
        if ($this->urlParser->isStatusId($id)){
            return null;
        }

        $url = $id . '/picture';
        $query = array(
            'type' => $size,
            'redirect' => false,
        );

        try {
            $response = $this->request($url, $query, $token);
        } catch (RequestException $e) {
            $this->dispatcher->dispatch(\AppEvents::EXCEPTION_ERROR, new ExceptionEvent($e, 'Error getting facebook image by API'));
            throw $e;
        }

        return isset($response['data']['url']) ? $response['data']['url'] : null;
    }

    public function requestVideo($id, $token)
    {
        $url = (string)$id;
        $query = array(
            'fields' => 'description,picture.width(500)'
        );

        return $this->request($url, $query, $token);
    }

    public function requestPage($id, $token)
    {
        $fields = array('name', 'description', 'picture.width(500)');
        $query = array('fields' => implode(',', $fields));

        return $this->request($id, $query, $token);
    }

    public function requestStatus($id, $token)
    {
        $fields = array('name', 'description', 'full_picture');
        $query = array('fields' => implode(',', $fields));

        return $this->request($id, $query, $token);
    }

    public function requestProfile($id, $token)
    {
        $fields = array('name', 'picture.width(500)');
        $query = array('fields' => implode(',', $fields));

        return $this->request($id, $query, $token);
    }
}
