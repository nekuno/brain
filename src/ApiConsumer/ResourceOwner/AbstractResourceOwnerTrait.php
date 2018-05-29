<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\Event\OAuthTokenEvent;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use Http\Client\HttpClient as HttpClientInterface;
use ApiConsumer\Exception\TokenException;
use Psr\Http\Message\ResponseInterface as Response;
use HWI\Bundle\OAuthBundle\DependencyInjection\Configuration;
use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use HWI\Bundle\OAuthBundle\Security\OAuthUtils;
use Model\Token\Token;
use Model\Token\TokensManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Http\HttpUtils;

trait AbstractResourceOwnerTrait
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    protected $expire_time_margin = 0;

    /**
     * @param HttpClientInterface $httpClient Buzz http client
     * @param HttpUtils $httpUtils Http utils
     * @param array $options Options for the resource owner
     * @param string $name Name for the resource owner
     * @param RequestDataStorageInterface $storage Request token storage
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(HttpClientInterface $httpClient, HttpUtils $httpUtils, array $options, $name, RequestDataStorageInterface $storage, EventDispatcherInterface $dispatcher)
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
     * @param Token $token
     * @return array
     */
    public function request($url, array $query = array(), Token $token = null)
    {
        if (null != $token && $token->getResourceOwner() === $this->getName()) {
            return $this->requestAsUser($url, $query, $token);
        } else {
            return $this->requestAsClient($url, $query);
        }
    }

    /***
     * @param string $url The url to fetch
     * @param array $query The query of the request
     * @param Token $token
     *
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\RequestException
     * @throws \Exception
     * @return array
     */
    public function requestAsUser($url, array $query = array(), Token $token)
    {
        if ($this->needsRefreshing($token)) {
            if (!$this->canRefresh($token)) {
                $this->manageTokenExpired($token, sprintf('Refresh token not present for user "%s"', $token->getUserId()));
            }

            try {
                $data = $this->refreshAccessToken($token->toArray());
            } catch (\Exception $e) {
                $data = array();
                $this->manageTokenExpired($token, $e->getMessage());
            }

            $this->addOauthData($data, $token);
            $event = new OAuthTokenEvent($token);
            $this->dispatcher->dispatch(\AppEvents::TOKEN_REFRESHED, $event);
        }

        $response = $this->sendAuthorizedRequest($this->options['base_url'] . $url, $query, $token);

        return $this->getResponseContent($response);
    }

    protected function manageTokenExpired(Token $token, $message)
    {
        $this->dispatchTokenExpired($token);
        $e = new TokenException($message);
        $e->setToken($token);
        throw $e;
    }

    private function dispatchTokenExpired(Token $token)
    {
        $event = new OAuthTokenEvent($token);
        $this->dispatcher->dispatch(\AppEvents::TOKEN_EXPIRED, $event);
    }

    protected function sendAuthorizedRequest($url, array $query = array(), Token $token = null)
    {
        if (Configuration::getResourceOwnerType($this->name) == 'oauth2' &&  $token->getResourceOwner() !== TokensManager::TUMBLR) {
            $query = $this->buildOauth2Query($query, $token);
        } else {
            $query = $this->buildOauth1Query($url, $query, $token);
        }

        $normalizedUrl = $this->normalizeUrl($url, $query);

        return $this->executeHttpRequest($normalizedUrl);
    }

    protected function buildOauth2Query($query, Token $token)
    {
        $query = array_merge($query, array('access_token' => $token->getOauthToken()));

        return $query;
    }

    protected function buildOauth1Query($url, $query, Token $token)
    {
        $parameters = array_merge(
            array(
                'oauth_consumer_key' => $this->options['consumer_key'],
                'oauth_timestamp' => time(),
                'oauth_nonce' => $this->generateNonce(),
                'oauth_version' => '1.0',
                'oauth_signature_method' => $this->options['signature_method'],
                'oauth_token' => $token->getOauthToken(),
            ),
            $query
        );

        $parameters['oauth_signature'] = $this->buildOauthSignature($url, $parameters, $token);

        return $parameters;
    }

    protected function buildOauthSignature($url, $parameters, Token $token)
    {
        return OAuthUtils::signRequest(
            'GET',
            $url,
            $parameters,
            $this->options['consumer_secret'],
            $token->getOauthTokenSecret(),
            $this->options['signature_method']
        );
    }

    protected function executeHttpRequest($url, $content = null, $headers = array(), $method = null)
    {
        $response = $this->httpRequest($url, $content, $headers, $method);

        if ($this->isAPILimitReached($response)) {
            $this->waitForAPILimit();
            $response = $this->httpRequest($url);
        }

        return $response;
    }

    protected function isAPILimitReached(Response $response)
    {
        return false;
    }

    protected function waitForAPILimit()
    {
    }

    protected function needsRefreshing(Token $token)
    {
        $hasExpirationTime = $token->getExpireTime() != 0;
        $hasExpired = $token->getExpireTime() <= time();

        return $hasExpirationTime && $hasExpired;
    }

    protected function canRefresh(Token $token)
    {
        return $token->getRefreshToken();
    }

    protected function addOauthData($data, Token $token)
    {
        $token->setOauthToken($data['access_token']);
        $token->setExpireTime(time() + (int)$data['expires_in'] - $this->expire_time_margin);
        $token->setRefreshToken(isset($data['refreshToken']) ? $data['refreshToken'] : $token->getRefreshToken());
    }

    public function requestAsClient($url, array $query = array())
    {
        $response = $this->executeHttpRequest($this->normalizeUrl($this->options['base_url'] . $url, $query));

        return $this->getResponseContent($response);
    }

    public function canRequestAsClient()
    {
        return false;
    }

    /**
     * Configure the option resolver
     *
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
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
}
