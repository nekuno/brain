<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\Event\OAuthTokenEvent;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use Event\ExceptionEvent;
use GuzzleHttp\Exception\RequestException;
use Model\User\TokensModel;
use Symfony\Component\OptionsResolver\OptionsResolver;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\FacebookResourceOwner as FacebookResourceOwnerBase;

/**
 * Class FacebookResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 * @method FacebookUrlParser getParser
 */
class FacebookResourceOwner extends FacebookResourceOwnerBase
{
	use AbstractResourceOwnerTrait {
		configureOptions as traitConfigureOptions;
	}

	protected $name = TokensModel::FACEBOOK;

	protected $expire_time_margin = 1728000;// 20 days because expired tokens can´t be refreshed

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
			'redirect_uri' => $this->getOption('redirect_uri'),
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
			'redirect_uri' => $this->getOption('redirect_uri'),
		);

		if (array_key_exists('refreshToken', $token) && null !== $token['refreshToken']) {
			$query['machine_id'] = $token['refreshToken'];
		}

		$request = $this->getAPIRequest($getAccessURL, $query);
		$response = $this->httpClient->send($request);
		$data = $response->json();

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
			$request = $this->getAPIRequest($getCodeURL, $query);
			$response = $this->httpClient->send($request);
			parse_str($response->getBody(), $data);
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

	/**
	 * {@inheritDoc}
	 */
	protected function configureOptions(OptionsResolver $resolver)
	{
		$this->traitConfigureOptions($resolver);

		$resolver->setDefaults(
			array(
				'base_url' => 'https://graph.facebook.com/v2.4/',
			)
		);

		$resolver->setDefined('redirect_uri');
	}

	public function getPicture($id, $token, $size = 'large')
	{
		if ($this->getParser()->isStatusId($id)){
			return null;
		}

		$url = $id . '/picture';
		$query = array(
			'type' => $size,
		);

		$request = $this->getAuthorizedRequest($this->options['base_url'] . $url, $query, $token);

		try {
			$response = $this->httpClient->send($request);
		} catch (RequestException $e) {
			var_dump($e->getMessage());
			$this->dispatcher->dispatch(\AppEvents::EXCEPTION_ERROR, new ExceptionEvent($e, 'Error getting facebook image by API'));
			throw $e;
		}

		$imageUrl = $response->getEffectiveUrl();

		return $imageUrl==$url? null : $imageUrl;
	}
}
