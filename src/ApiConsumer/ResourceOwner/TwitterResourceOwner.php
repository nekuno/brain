<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\LinkProcessor\Processor\TwitterProcessor\AbstractTwitterProcessor;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use Buzz\Exception\RequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Model\Link\Link;
use Model\Token\Token;
use Symfony\Component\OptionsResolver\OptionsResolver;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\TwitterResourceOwner as TwitterResourceOwnerBase;

class TwitterResourceOwner extends TwitterResourceOwnerBase
{
    use AbstractResourceOwnerTrait {
        AbstractResourceOwnerTrait::configureOptions as traitConfigureOptions;
        AbstractResourceOwnerTrait::__construct as private traitConstructor;
    }

    /** @var  TwitterUrlParser */
    protected $urlParser;

    const PROFILES_PER_LOOKUP = 100;

    public function __construct($httpClient, $httpUtils, $options, $name, $storage, $dispatcher)
    {
        $this->traitConstructor($httpClient, $httpUtils, $options, $name, $storage, $dispatcher);
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
                'base_url' => 'https://api.twitter.com/1.1/',
                'realm' => null,
            )
        );
    }

    public function requestAsClient($url, array $query = array())
    {
        $clientToken = $this->getOption('client_credential')['application_token'];
        $url = $this->getOption('base_url') . $url;

        $headers = array();
        if (!empty($clientToken)) {
            $headers = array('Authorization: Bearer ' . $clientToken);
        }

        $response = $this->httpRequest($this->normalizeUrl($url, $query), null, array(), $headers);

        return $this->getResponseContent($response);
    }

    public function canRequestAsClient()
    {
        return true;
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

    /**
     * @param $parameter
     * @param array $userIds
     * @param Token|null $token
     * @return array[]|bool Array of user arrays
     */
    public function lookupUsersBy($parameter, array $userIds, Token $token = null)
    {
        if (!in_array($parameter, array('user_id', 'screen_name'))) {
            return false;
        }

        $chunks = array_chunk($userIds, self::PROFILES_PER_LOOKUP);
        $url = 'users/lookup.json';

        $responses = array();
        //TODO: Array to string conversion here
        foreach ($chunks as $chunk) {
            $query = array($parameter => implode(',', $chunk));
            $response = $this->request($url, $query, $token);
            $responses = array_merge($responses, $response);
        }

        return $responses;
    }

    protected function isAPILimitReached(Response $response)
    {
        return $response->getStatusCode() === 429;
    }

    protected function waitForAPILimit()
    {
        $fifteenMinutes = 60 * 15;
        sleep($fifteenMinutes);
    }

    /**
     * @param $content array[]
     * @return Link[]
     */
    //TODO: Move this to Processor and use getImages. Is it really necessary in TwitterFollowingFetcher? Are we doing this multiple times?
    public function buildProfilesFromLookup(array $content)
    {
        foreach ($content as &$user) {
            $user = $this->buildProfileFromLookup($user);
        }

        return $content;
    }

    /**
     * @param $user array
     * @return Link
     */
    protected function buildProfileFromLookup(array $user)
    {
        if (!$user) {
            return null;
        }

        $profile = new Link();
        $profile->setUrl($this->getUserUrl($user));
        $profile->setTitle(isset($user['name']) ? $user['name'] : $profile->getUrl());
        $profile->setDescription(isset($user['description']) ? $user['description'] : $profile->getTitle());
        $profile->setThumbnail($this->urlParser->getOriginalProfileUrl($user, null));
        $profile->setThumbnailMedium($this->urlParser->getMediumProfileUrl($user, null));
        $profile->setThumbnailSmall($this->urlParser->getSmallProfileUrl($user, null));
        $profile->setCreated(1000 * time());
        $profile->addAdditionalLabels(AbstractTwitterProcessor::TWITTER_LABEL);
        $profile->setProcessed(true);

        return $profile;
    }

    public function requestProfileUrl(Token $token)
    {
        try {
            $settingsUrl = 'account/settings.json';
            $account = $this->requestAsUser($settingsUrl, array(), $token);
        } catch (RequestException $e) {
            return null;
        }

        return $this->getUserUrl($account);
    }

    public function getUserUrl(array $user)
    {
        return isset($user['screen_name']) ? $this->urlParser->buildUserUrl($user['screen_name']) : null;
    }

    public function requestStatus($statusId)
    {
        $query = array('id' => (int)$statusId);
        $apiResponse = $this->requestAsClient('statuses/show.json', $query);

        return $apiResponse;
    }

//	public function dispatchChannel(array $data)
//	{
//		$url = isset($data['url']) ? $data['url'] : null;
//		$username = isset($data['username']) ? $data['username'] : null;
//		if (!$username && $url) {
//			throw new \Exception ('Cannot add twitter channel with username and url not set');
//		}
//
//		$this->dispatcher->dispatch(\AppEvents::CHANNEL_ADDED, new ChannelEvent($this->getName(), $url, $username));
//	}
}
