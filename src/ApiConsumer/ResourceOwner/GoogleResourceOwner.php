<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\LinkProcessor\LinkAnalyzer;
use Model\User\TokensModel;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GoogleResourceOwner as GoogleResourceOwnerBase;

/**
 * Class GoogleResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class GoogleResourceOwner extends GoogleResourceOwnerBase
{
	use AbstractResourceOwnerTrait {
		AbstractResourceOwnerTrait::configureOptions as traitConfigureOptions;
	}

	protected $name = TokensModel::GOOGLE;

	public function sendAuthorizedRequest($url, array $query = array(), array $token = array())
	{
		$headers = array();
		if (array_key_exists('network', $token) && $token['network'] == LinkAnalyzer::YOUTUBE) {
			$token = $this->getOption('client_credential')['application_token'];
			$headers = array('Authorization: Bearer ' . $token);
		} else {
			$query['key'] = $this->getOption('client_credential')['application_token'];
		}

		return $this->httpRequest($this->normalizeUrl($url, $query), null, $headers);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function configureOptions(OptionsResolverInterface $resolver)
	{
		$this->traitConfigureOptions($resolver);

		$resolver->setDefaults(array(
			'base_url' => 'https://www.googleapis.com/',
		));
	}

	public function refreshAccessToken($token, array $extraParameters = array())
	{
		$refreshToken = $token['refreshToken'];
		$url = 'https://accounts.google.com/o/oauth2/token';
		$parameters = array(
			'refresh_token' => $refreshToken,
			'grant_type' => 'refresh_token',
			'client_id' => $this->options['consumer_key'],
			'client_secret' => $this->options['consumer_secret'],
		);

		$response = $this->httpRequest($url, array('body' => $parameters));
		$data = $this->getResponseContent($response);

		return $data;
	}
}
