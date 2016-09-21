<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\Event\OAuthTokenEvent;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use Event\ExceptionEvent;
use GuzzleHttp\Exception\RequestException;
use Model\User\TokensModel;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\FacebookResourceOwner as FacebookResourceOwnerBase;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FacebookResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 * @method FacebookUrlParser getParser
 */
class FacebookResourceOwner extends FacebookResourceOwnerBase
{
	use AbstractResourceOwnerTrait {
		AbstractResourceOwnerTrait::configureOptions as traitConfigureOptions;
		AbstractResourceOwnerTrait::__construct as private traitConstructor;
	}

	protected $name = TokensModel::FACEBOOK;

	public function __construct($httpClient, $httpUtils, $options, $name, $storage, $dispatcher)
	{
		$this->traitConstructor($httpClient, $httpUtils, $options, $name, $storage, $dispatcher);
        $this->expire_time_margin = 1728000;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function configureOptions(OptionsResolverInterface $resolver)
	{
		$this->traitConfigureOptions($resolver);
		parent::configureOptions($resolver);

		$resolver->setDefaults(
			array(
				'base_url' => 'https://graph.facebook.com/v2.4/',
			)
		);

		$resolver->setDefined('redirect_uri');
	}

	/**
	 * We use Facebook system for getting new long-lived tokens
	 * and assume machine-id as a non-obligatory refreshToken
	 * @param array $token
	 * @param array $extraParameters
	 * @return array
	 * @throws RequestException
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
		$query = array(
			'code' => $this->getResponseContent($response)['code'],
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

	public function forceRefreshAccessToken($token)
	{
		$data = $this->refreshAccessToken($token);
		$token = $this->addOauthData($data, $token);
		$event = new OAuthTokenEvent($token);
		$this->dispatcher->dispatch(\AppEvents::TOKEN_REFRESHED, $event);

		return $token;
	}

	public function extend($token)
	{
		$getCodeURL = 'https://graph.facebook.com/oauth/access_token';
		$query = array(
			'grant_type' => 'fb_exchange_token',
			'client_id' => $this->getOption('consumer_key'),
			'client_secret' => $this->getOption('consumer_secret'),
			'fb_exchange_token' => $token['oauthToken'],
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

		$token = $this->addOauthData($data, $token);
		$event = new OAuthTokenEvent($token);
		$this->dispatcher->dispatch(\AppEvents::TOKEN_REFRESHED, $event);

		return $token;
	}

	public function getPicture($id, $token, $size = 'large')
	{
		if ($this->getParser()->isStatusId($id)){
			return null;
		}

		$url = $id . '/picture';
		$query = array(
			'type' => $size,
			'redirect' => false,
		);

		try {
			$response = $this->sendAuthorizedRequest($this->options['base_url'] . $url, $query, $token);
		} catch (RequestException $e) {
			var_dump($e->getMessage());
			$this->dispatcher->dispatch(\AppEvents::EXCEPTION_ERROR, new ExceptionEvent($e, 'Error getting facebook image by API'));
			throw $e;
		}

		$response = $this->getResponseContent($response);

		return isset($response['data']['url']) ? $response['data']['url'] : null;
	}
}
