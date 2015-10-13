<?php

namespace Http\OAuth\ResourceOwner;

use ApiConsumer\Event\OAuthTokenEvent;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\ResponseInterface;
use Http\Exception\TokenException;
use Http\OAuth\ResourceOwner\ClientCredential\ClientCredentialInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
abstract class AbstractResourceOwner implements ResourceOwnerInterface
{
    protected $name = 'generic';

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;
    /**
     * @var array Configuration
     */
    protected $options = array();
    /**
     * @var \Http\OAuth\ResourceOwner\ClientCredential\ClientCredentialInterface
     */
    private $clientCredential;

    protected $expire_time_margin = 0;

    /**
     * @param ClientInterface $httpClient
     * @param \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher
     * @param array $options
     */
    public function __construct(ClientInterface $httpClient, EventDispatcher $dispatcher, array $options = array())
    {
        $this->httpClient = $httpClient;
        $this->dispatcher = $dispatcher;

        // Resolve merged options
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);
        $this->options = $options;

        if (isset($this->options['client_credential_class'])) {
            $clientCredentialClass = $this->options['client_credential_class'];
            $clientCredentialOptions = array();
            if (isset($this->options['client_credential'])) {
                $clientCredentialOptions = $this->options['client_credential'];
            }
            $this->clientCredential = new $clientCredentialClass($clientCredentialOptions);
        }

        $this->configure();
    }

    /**
     * Gives a chance for extending providers to customize stuff
     */
    public function configure()
    {

    }

    /**
     * {@inheritDoc}
     */
    public function getOption($name)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new \InvalidArgumentException(sprintf('Unknown option "%s"', $name));
        }

        return $this->options[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Performs an authorized HTTP request
     *
     * @param string $url The url to fetch
     * @param array $query The query of the request
     * @param array $token The token values as an array
     *
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\RequestException
     * @throws \Exception
     * @return array
     */
    public function authorizedHttpRequest($url, array $query = array(), array $token = array())
    {
        if (isset($token['expireTime']) && ($token['expireTime'] <= time() && $token['expireTime'] != 0)) {

            if (!$token['refreshToken']) {
                $event = new OAuthTokenEvent($token);
                $this->dispatcher->dispatch(\AppEvents::TOKEN_EXPIRED, $event);
                $e = new TokenException(sprintf('Refresh token not present for user "%s"', $token['username']));
                $e->setToken($token);
                throw $e;
            }

            try {
                $data = $this->refreshAccessToken($token);
            } catch (\Exception $e) {
                $event = new OAuthTokenEvent($token);
                $this->dispatcher->dispatch(\AppEvents::TOKEN_EXPIRED, $event);
                $e = new TokenException($e->getMessage(), $e->getCode(), $e->getPrevious());
                $e->setToken($token);
                throw $e;
            }

            $token = $this->addOauthData($data, $token);
            $event = new OAuthTokenEvent($token);
            $this->dispatcher->dispatch(\AppEvents::TOKEN_REFRESHED, $event);
        }

        $request = $this->getAuthorizedRequest($this->options['base_url'] . $url, $query, $token);

        try {
            $response = $this->httpClient->send($request);
        } catch (RequestException $e) {
            throw $e;
        }

        return $this->getResponseContent($response);
    }

    protected function addOauthData($data, $token)
    {
        if (!$data['access_token']) {
            $this->notifyUserByEmail($token);
        }

        $token['oauthToken'] = $data['access_token'];
        $token['expireTime'] = $token['createdTime'] + $data['expires_in'] - $this->expire_time_margin;
        $token['refreshToken'] = isset($data['refreshToken']) ? $data['refreshToken'] : null;

        return $token;
    }

    /**
     * @param $url
     * @param array $query
     * @param array $token
     * @return Request
     */
    public function getAPIRequest($url, array $query = array(), array $token = array())
    {
        $clientConfig = array(
            'query' => $query,
        );

        return $this->httpClient->createRequest('GET', $url, $clientConfig);
    }

    public function authorizedAPIRequest($url, array $query = array(), array $token = array())
    {

        $request = $this->getAPIRequest($this->options['base_url'] . $url, $query, $token);
        var_dump($request->getUrl());
        try {
            $response = $this->httpClient->send($request);
        } catch (RequestException $e) {
            throw $e;
        }

        return $this->getResponseContent($response);
    }

    /**
     * Refresh an access token using a refresh token.
     *
     * @param array $token Array of user data
     * @param array $extraParameters An array of parameters to add to the url
     *
     * @throws \Exception
     * @return array Array containing the access token and it's 'expires_in' value,
     *               along with any other parameters returned from the authentication
     *               provider.
     *
     */
    public function refreshAccessToken($token, array $extraParameters = array())
    {
        throw new \Exception('OAuth error: "Method unsupported."');
    }

    public function forceRefreshAccessToken($token)
    {
        throw new \Exception('OAuth error: "Method unsupported."');
    }

    protected function getClientToken()
    {
        if ($this->clientCredential instanceof ClientCredentialInterface) {
            return $this->clientCredential->getClientToken();
        }

        return '';
    }

    /**
     * Get the 'parsed' content based on the response headers.
     *
     * @param ResponseInterface $response
     *
     * @return array
     */
    protected function getResponseContent(ResponseInterface $response)
    {
        return $response->json();
    }

    /**
     * {@inheritDoc}
     */
    protected function getAuthorizedRequest($url, array $query = array(), array $token = array())
    {
        $clientConfig = array(
            'query' => $query,
        );

        return $this->httpClient->createRequest('GET', $url, $clientConfig);
    }

    /**
     * Configure the option resolver
     *
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            array(
                'consumer_key',
                'consumer_secret',
                'class'
            )
        );
        $resolver->setDefined(
            array(
                'client_credential_class',
                'client_credential'
            )
        );
    }

    /**
     * @param $user
     * @return string
     */
    public function getUsername($user)
    {
        if (!$user) return null;
        $url = array_key_exists('url', $user)? $user['url'] : null;
        $parts = explode('/', $url);
        return end($parts);
    }


}
