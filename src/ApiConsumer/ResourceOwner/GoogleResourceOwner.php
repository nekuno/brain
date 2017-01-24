<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GoogleResourceOwner as GoogleResourceOwnerBase;


class GoogleResourceOwner extends GoogleResourceOwnerBase
{
    use AbstractResourceOwnerTrait {
        AbstractResourceOwnerTrait::configureOptions as traitConfigureOptions;
        AbstractResourceOwnerTrait::__construct as private traitConstructor;
    }

    /** @var YoutubeUrlParser */
    protected $urlParser;

    public function __construct($httpClient, $httpUtils, $options, $name, $storage, $dispatcher)
    {
        $this->traitConstructor($httpClient, $httpUtils, $options, $name, $storage, $dispatcher);
    }

    protected function sendAuthorizedRequest($url, array $query = array(), array $token = array())
    {
        $query += $this->getOauthToken($token);

        return $this->httpRequest($this->normalizeUrl($url, $query));
    }

    public function sendYoutubeAuthorizedRequest($url, $query = array())
    {
        $token = $this->getOption('client_credential')['application_token'];
        $headers = array('Authorization: Bearer ' . $token);

        $response = $this->httpRequest($this->normalizeUrl($url, $query), null, $headers);

        return $this->getResponseContent($response);
    }

    protected function getOauthToken($token) {
        return isset($token['oauthToken']) ? array('access_token' => $token['oauthToken']) : array('key' => $this->getOption('client_credential')['application_token']);
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
                'base_url' => 'https://www.googleapis.com/',
            )
        );
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

        $response = $this->doGetTokenRequest($url, $parameters);
        $data = $this->getResponseContent($response);

        return $data;
    }

    protected function addOauthData($data, $token)
    {
        $token['oauthToken'] = $data['access_token'];
        $token['expireTime'] = time() + (int)$data['expires_in'] - $this->expire_time_margin;
        $token['refreshToken'] = isset($data['refreshToken']) ? $data['refreshToken'] : isset($token['refreshToken']) ? $token['refreshToken'] : null;

        return $token;
    }

    public function requestVideoSearch($queryString)
    {
        $url = 'youtube/v3/search';
        $query = array(
            'q' => $queryString,
            'part' => 'id,snippet',
            'type' => 'video'
        );
        $response = $this->sendAuthorizedRequest($this->options['base_url'] . $url, $query);

        $content = $this->getResponseContent($response);

        return isset($content['items']) ? $content['items'] : array();
    }

    public function requestVideo(array $itemId)
    {
        $url = 'youtube/v3/videos';
        $itemApiParts = 'snippet,statistics,topicDetails';

        return $this->requestYoutubeItem($url, $itemApiParts, $itemId);
    }

    public function requestChannel(array $itemId)
    {
        $url = 'youtube/v3/channels';
        $itemApiParts = 'snippet,brandingSettings,contentDetails,invideoPromotion,statistics,topicDetails';

        return $this->requestYoutubeItem($url, $itemApiParts, $itemId);
    }

    public function requestPlaylist(array $itemId)
    {
        $url = 'youtube/v3/playlists';
        $itemApiParts = 'snippet,status';

        return $this->requestYoutubeItem($url, $itemApiParts, $itemId);
    }

    private function requestYoutubeItem($url, $parts, array $itemId)
    {
        $itemKey = array_keys($itemId)[0];

        $query = $query = array(
            'part' => $parts,
            $itemKey => $itemId[$itemKey],
        );

        $response = $this->sendAuthorizedRequest($this->options['base_url'] . $url, $query);

        return $this->getResponseContent($response);

    }
}
