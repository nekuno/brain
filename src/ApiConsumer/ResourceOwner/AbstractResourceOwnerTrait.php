<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\Event\OAuthTokenEvent;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use Buzz\Client\ClientInterface as HttpClientInterface;
use ApiConsumer\Exception\TokenException;
use Http\OAuth\ResourceOwner\ClientCredential\ClientCredentialInterface;
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
 */
trait AbstractResourceOwnerTrait
{
	/**
	 * @var EventDispatcher
	 */
	protected $dispatcher;

	/**
	 * @var \Http\OAuth\ResourceOwner\ClientCredential\ClientCredentialInterface
	 */
	protected $clientCredential;

	/**
	 * @var UrlParser
	 */
	protected $urlParser;

	/**
	 * @param HttpClientInterface         $httpClient Buzz http client
	 * @param HttpUtils                   $httpUtils  Http utils
	 * @param array                       $options    Options for the resource owner
	 * @param string                      $name       Name for the resource owner
	 * @param RequestDataStorageInterface $storage    Request token storage
	 * @param EventDispatcher             $dispatcher
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

		if (isset($options['parser_class'])){
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

	public function getParser()
	{
		return $this->urlParser;
	}

	public function getClient()
	{
		return $this->httpClient;
	}

	public function sendAuthorizedRequest($url, array $query = array(), array $token = array())
	{
		if (Configuration::getResourceOwnerType($this->name) == 'oauth2') {
			// oauth2
			$query = array_merge($query, array('access_token' => $token['oauthToken']));

			return $this->httpRequest($this->normalizeUrl($url, $query));
		} else {
			// oauth1
			$parameters = array_merge(array(
				'oauth_consumer_key'     => $this->options['consumer_key'],
				'oauth_timestamp'        => time(),
				'oauth_nonce'            => $this->generateNonce(),
				'oauth_version'          => '1.0',
				'oauth_signature_method' => $this->options['signature_method'],
				'oauth_token'            => isset($token['oauthToken']) ? $token['oauthToken'] : null,
			), $query);

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
				$this->dispatchTokenExpired($token);
				$e = new TokenException(sprintf('Refresh token not present for user "%s"', $token['username']));
				$e->setToken($token);
				throw $e;
			}

			try {
				$data = $this->refreshAccessToken($token);
			} catch (\Exception $e) {
				$this->dispatchTokenExpired($token);
				$e = new TokenException($e->getMessage(), $e->getCode(), $e->getPrevious());
				$e->setToken($token);
				throw $e;
			}

			$token = $this->addOauthData($data, $token);
			$event = new OAuthTokenEvent($token);
			$this->dispatcher->dispatch(\AppEvents::TOKEN_REFRESHED, $event);
		}

		$response = $this->sendAuthorizedRequest($this->options['base_url'] . $url, $query, $token);

		return $this->getResponseContent($response);
	}

	private function dispatchTokenExpired($token)
	{
		if (isset($token['network']) && $token['network'] == $this->getName()){
			$event = new OAuthTokenEvent($token);
			$this->dispatcher->dispatch(\AppEvents::TOKEN_EXPIRED, $event);
		}

	}

	protected function addOauthData($data, $token)
	{
		$token['oauthToken'] = $data['access_token'];
		$token['expireTime'] = (int)$token['createdTime'] + (int)$data['expires_in'] - $this->expire_time_margin;
		$token['refreshToken'] = isset($data['refreshToken']) ? $data['refreshToken'] : null;

		return $token;
	}

	public function authorizedAPIRequest($url, array $query = array(), array $token = array())
	{
		$response = $this->httpRequest($this->normalizeUrl($this->options['base_url'] . $url, $query));

		return $this->getResponseContent($response);
	}

	protected function getClientToken()
	{
		if ($this->clientCredential instanceof ClientCredentialInterface) {
			return $this->clientCredential->getClientToken();
		}

		return '';
	}

	protected function getApplicationToken()
	{
		if ($this->clientCredential instanceof ClientCredentialInterface) {
			return $this->clientCredential->getApplicationToken();
		}

		return '';
	}

	/**
	 * Configure the option resolver
	 *
	 * @param OptionsResolverInterface $resolver
	 */
	protected function configureOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setRequired(
			array(

			)
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
		$resolver->setDefaults(array(
			'realm'            => null,
			'signature_method' => 'HMAC-SHA1',
			'client_id' => '',
			'client_secret' => '',
		));
	}

	/**
	 * @param $user
	 * @return string | null
	 */
	public function getUsername($user)
	{
		if (!$user) return null;
		$url = array_key_exists('url', $user) ? $user['url'] : null;
		$parts = explode('/', $url);
		return end($parts);
	}
}
