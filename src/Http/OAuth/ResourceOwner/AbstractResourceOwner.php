<?php

namespace Http\OAuth\ResourceOwner;

use ApiConsumer\Event\OAuthTokenEvent;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use AppEvents;

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


            try {
                $data = $this->refreshAccessToken($token['refreshToken']);
            } catch (\Exception $e) {
                // The resource owner not implements the method refreshAccessToken
                $this->notifyUserByEmail($token);
                throw $e;
            }

            if (!$data['access_token']) {
                $this->notifyUserByEmail($token);
            }

            $token['oauthToken'] = $data['access_token'];
            $token['createdTime'] = time();
            $token['expireTime'] = $token['createdTime'] + $data['expires_in'];
            $event = new OAuthTokenEvent($token);
            $this->dispatcher->dispatch(AppEvents::TOKEN_REFRESHED, $event);
        }

        $request = $this->getAuthorizedRequest($this->options['base_url'] . $url, $query, $token);

        try {
            $response = $this->httpClient->send($request);
        } catch (RequestException $e) {
            throw $e;
        }

        return $this->getResponseContent($response);
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
     * @param string $refreshToken Refresh token
     * @param array $extraParameters An array of parameters to add to the url
     *
     * @throws \Exception
     * @return array Array containing the access token and it's 'expires_in' value,
     *               along with any other parameters returned from the authentication
     *               provider.
     *
     */
    public function refreshAccessToken($refreshToken, array $extraParameters = array())
    {
        throw new \Exception('OAuth error: "Method unsupported."');
    }

    /**
     * Configure the option resolver
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(
            'consumer_key',
            'consumer_secret',
            'class'
        ));
        $resolver->setOptional(array(
            'api_key',
        ));
    }

    /**
     * @param array $token
     */
    protected function notifyUserByEmail(array $token)
    {
        $event = new OAuthTokenEvent($token);
        $this->dispatcher->dispatch(AppEvents::TOKEN_EXPIRED, $event);
    }

}
