<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\Event\ChannelEvent;
use Buzz\Exception\RequestException;
use Buzz\Message\Response;
use Model\User\TokensModel;
use Service\LookUp\LookUp;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\TwitterResourceOwner as TwitterResourceOwnerBase;

/**
 * Class TwitterResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class TwitterResourceOwner extends TwitterResourceOwnerBase
{
	use AbstractResourceOwnerTrait {
		AbstractResourceOwnerTrait::configureOptions as traitConfigureOptions;
		AbstractResourceOwnerTrait::__construct as private traitConstructor;
	}

	protected $name = TokensModel::TWITTER;

	public function __construct($httpClient, $httpUtils, $options, $name, $storage, $dispatcher)
	{
		$this->traitConstructor($httpClient, $httpUtils, $options, $name, $storage, $dispatcher);
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
				'base_url' => 'https://api.twitter.com/1.1/',
				'realm' => null,
			)
		);
	}

	public function authorizedAPIRequest($url, array $query = array(), array $token = array())
	{
		$clientToken = $this->getOption('client_credential')['application_token'];
        $url = $this->getOption('base_url').$url;

		$headers = array();
		if (!empty($clientToken)) {
			$headers = array('Authorization: Bearer ' . $clientToken);
		}

		$username = $this->getUsername($token);
		if ($username) {
			$query += array('screen_name' => $username);
		}

		$response = $this->httpRequest($this->normalizeUrl($url, $query), null, array(), $headers);

		return $this->getResponseContent($response);
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

	public function lookupUsersBy($parameter, array $userIds, array $token = array())
	{
		if ($parameter !== 'user_id' && $parameter !== 'screen_name') {
			return false;
		}

		$chunks = array_chunk($userIds, 100);
		$baseUrl = $this->getOption('base_url');
		$url = $baseUrl . 'users/lookup.json';

		$users = array();
		foreach ($chunks as $chunk) {
			$query = array($parameter => implode(',', $chunk));
            /** @var Response $response */
			$response = $this->sendAuthorizedRequest($url, $query, $token);
            //TODO: Generalize "if too many requests, wait this->time_window and retry"
            if ($response->getStatusCode() === 429){
                sleep(60*15);
                $response = $this->sendAuthorizedRequest($url, $query, $token);
            }
			$users = array_merge($users, $this->getResponseContent($response));
		}

		return $users;
	}

	public function buildProfileFromLookup($user)
	{
		if (!$user) {
			return $user;
		}

		$profile = array(
			'description' => isset($user['description']) ? $user['description'] : $user['name'],
			'url' => isset($user['screen_name']) ? 'https://twitter.com/' . $user['screen_name'] : null,
			'thumbnail' => isset($user['profile_image_url']) ? str_replace('_normal', '', $user['profile_image_url']) : null,
			'additionalLabels' => array('Creator'),
			'resource' => TokensModel::TWITTER,
			'timestamp' => 1000 * time(),
            'processed' => 1
		);
        $profile['title'] = isset($user['name']) ? $user['name'] : $profile['url'];

		return $profile;
	}

	public function getProfileUrl(array $token)
	{
		if (isset($token['screenName'])) {
			$screenName = $token['screenName'];
		} else {
			try {
				$account = $this->authorizedHttpRequest('account/settings.json', array(), $token);
			} catch (RequestException $e) {
				return null;
			}

			$screenName = $account['screen_name'];
		}

		return LookUp::TWITTER_BASE_URL . $screenName;
	}

	public function requestStatus($statusId)
    {
        $query = array('id' => (int)$statusId);
        $apiResponse = $this->authorizedAPIRequest('statuses/show.json', $query);

        return $apiResponse;
    }

	public function dispatchChannel(array $data)
	{
		$url = isset($data['url']) ? $data['url'] : null;
		$username = isset($data['username']) ? $data['username'] : null;
		if (!$username && $url) {
			throw new \Exception ('Cannot add twitter channel with username and url not set');
		}

		$this->dispatcher->dispatch(\AppEvents::CHANNEL_ADDED, new ChannelEvent($this->getName(), $url, $username));
	}
}
