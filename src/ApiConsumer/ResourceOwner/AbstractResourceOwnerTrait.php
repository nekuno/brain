<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\Event\OAuthTokenEvent;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use Buzz\Client\ClientInterface as HttpClientInterface;
use ApiConsumer\Exception\TokenException;
use HWI\Bundle\OAuthBundle\DependencyInjection\Configuration;
use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use HWI\Bundle\OAuthBundle\Security\OAuthUtils;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Buzz\Message\RequestInterface as HttpRequestInterface;

/**
 * Trait AbstractResourceOwnerTrait
 *
 * @package ApiConsumer\ResourceOwner
 * @method normalizeUrl ($a, $b)
 * @method generateNonce
 * @method getResponseContent($a)
 */
trait AbstractResourceOwnerTrait
{
    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    protected $name;

    /** @var UrlParser */
    protected $urlParser;

    protected $expire_time_margin = 0;

    /**
     * @param HttpClientInterface $httpClient Buzz http client
     * @param HttpUtils $httpUtils Http utils
     * @param array $options Options for the resource owner
     * @param string $name Name for the resource owner
     * @param RequestDataStorageInterface $storage Request token storage
     * @param EventDispatcher $dispatcher
     */
    public function __construct(HttpClientInterface $httpClient, HttpUtils $httpUtils, array $options, $name, RequestDataStorageInterface $storage, EventDispatcher $dispatcher)
    {
        $this->httpClient = $httpClient;
        $this->name = $name;
        $this->httpUtils = $httpUtils;
        $this->storage = $storage;
        $this->dispatcher = $dispatcher;

        if (!empty($options['paths'])) {
            $this->addPaths($options['paths']);
        }
        unset($options['paths']);

        if (!empty($options['options'])) {
            $options += $options['options'];
            unset($options['options']);
        }
        unset($options['options']);

        // Resolve merged options
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);
        $this->options = $options;

        if (isset($options['parser_class'])) {
            $this->urlParser = new $options['parser_class']();
        } else {
            $this->urlParser = new UrlParser();
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

    public function getClient()
    {
        return $this->httpClient;
    }

    /**
     * Performs request as an user if token is provided, as Nekuno otherwise
     * @param $url
     * @param array $query
     * @param array $token
     * @return array
     */
    public function request($url, array $query = array(), array $token = array())
    {
        if (!empty($token)) {
            return $this->authorizedHttpRequest($url, $query, $token);
        } else {
            return $this->authorizedAPIRequest($url, $query);
        }
    }

    /**
     * Performs an authorized HTTP request as User
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
        if ($this->needsRefreshing($token)) {
            if (!$this->canRefresh($token)) {
                $this->manageTokenExpired($token, sprintf('Refresh token not present for user "%s"', $token['username']));
            }

            try {
                $data = $this->refreshAccessToken($token);
            } catch (\Exception $e) {
                $data = array();
                $this->manageTokenExpired($token, $e->getMessage());
            }

            $token = $this->addOauthData($data, $token);
            $event = new OAuthTokenEvent($token);
            $this->dispatcher->dispatch(\AppEvents::TOKEN_REFRESHED, $event);
        }

        $response = $this->sendAuthorizedRequest($this->options['base_url'] . $url, $query, $token);

        return $this->getResponseContent($response);
    }

    protected function manageTokenExpired($token, $message)
    {
        $this->dispatchTokenExpired($token);
        $e = new TokenException($message);
        $e->setToken($token);
        throw $e;
    }

    private function dispatchTokenExpired($token)
    {
        if (isset($token['network']) && $token['network'] == $this->getName()) {
            $event = new OAuthTokenEvent($token);
            $this->dispatcher->dispatch(\AppEvents::TOKEN_EXPIRED, $event);
        }
    }

    /**
     * @param $url
     * @param array $query
     * @param array $token
     * @return \HttpResponse
     */
    protected function sendAuthorizedRequest($url, array $query = array(), array $token = array())
    {
        if (Configuration::getResourceOwnerType($this->name) == 'oauth2') {
            // oauth2
            $query = array_merge($query, array('access_token' => $token['oauthToken']));

            return $this->httpRequest($this->normalizeUrl($url, $query));
        } else {
            // oauth1
            $parameters = array_merge(
                array(
                    'oauth_consumer_key' => $this->options['consumer_key'],
                    'oauth_timestamp' => time(),
                    'oauth_nonce' => $this->generateNonce(),
                    'oauth_version' => '1.0',
                    'oauth_signature_method' => $this->options['signature_method'],
                    'oauth_token' => isset($token['oauthToken']) ? $token['oauthToken'] : null,
                ),
                $query
            );

            $parameters['oauth_signature'] = OAuthUtils::signRequest(
                HttpRequestInterface::METHOD_GET,
                $url,
                $parameters,
                $this->options['consumer_secret'],
                isset($token['oauthTokenSecret']) ? $token['oauthTokenSecret'] : null,
                $this->options['signature_method']
            );

            return $this->httpRequest($this->normalizeUrl($url, $parameters));
        }
    }

    protected function needsRefreshing($token)
    {
        return isset($token['expireTime']) && ($token['expireTime'] <= time() && $token['expireTime'] != 0);
    }

    protected function canRefresh($token)
    {
        return isset($token['refreshToken']);
    }

    protected function addOauthData($data, $token)
    {
        $token['oauthToken'] = $data['access_token'];
        $token['expireTime'] = (int)$token['createdTime'] + (int)$data['expires_in'] - $this->expire_time_margin;
        $token['refreshToken'] = isset($data['refreshToken']) ? $data['refreshToken'] : isset($token['refreshToken']) ? $token['refreshToken'] : null;

        return $token;
    }

    /** Request as Nekuno */
    public function authorizedAPIRequest($url, array $query = array(), array $token = array())
    {
        $response = $this->httpRequest($this->normalizeUrl($this->options['base_url'] . $url, $query));

        return $this->getResponseContent($response);
    }

    /**
     * Configure the option resolver
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array()
        );
        $resolver->setDefined(
            array(
                'client_credential',
                'parser_class',
                'consumer_key',
                'consumer_secret',
                'class',
            )
        );
        $resolver->setDefaults(
            array(
                'realm' => null,
                'signature_method' => 'HMAC-SHA1',
                'client_id' => '',
                'client_secret' => '',
            )
        );
    }

    /**
     * @param $user
     * @return string | null
     */
    public function getUsername($user)
    {
        if (!$user) {
            return null;
        }
        $url = array_key_exists('url', $user) ? $user['url'] : null;
        $parts = explode('/', $url);

        return end($parts);
    }
}
