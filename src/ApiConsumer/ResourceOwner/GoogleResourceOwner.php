<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use Model\Token\Token;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

    public function canRequestAsClient()
    {
        return true;
    }

    public function requestAsClient($url, array $query = array())
    {
        $url = $this->options['base_url'] . $url;

        $token = $this->getOption('client_credential')['application_token'];
        $query += array('key' => $token);

        $response = $this->httpRequest($this->normalizeUrl($url, $query));

        return $this->getResponseContent($response);
    }

    protected function sendAuthorizedRequest($url, array $query = array(), Token $token = null)
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

    protected function getOauthToken(Token $token)
    {
        $applicationToken = $this->getOption('client_credential')['application_token'];
        $oauthToken = $token->getOauthToken();

        return $oauthToken ? array('access_token' => $oauthToken) : array('key' => $applicationToken);
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

    public function requestVideoSearch($queryString)
    {
        $url = 'youtube/v3/search';
        $query = array(
            'q' => $queryString,
            'part' => 'id,snippet',
            'type' => 'video'
        );
        $content = $this->request($url, $query);

        return isset($content['items']) ? $content['items'] : array();
    }

    public function requestVideo(array $itemId, Token $token = null)
    {
        $url = 'youtube/v3/videos';
        $itemApiParts = 'snippet,statistics,topicDetails';

        return $this->requestYoutubeItem($url, $itemApiParts, $itemId, $token);
    }

    public function requestChannel(array $itemId, Token $token = null)
    {
        $url = 'youtube/v3/channels';
        $itemApiParts = 'snippet,brandingSettings,contentDetails,invideoPromotion,statistics,topicDetails';

        return $this->requestYoutubeItem($url, $itemApiParts, $itemId, $token);
    }

    public function requestPlaylist(array $itemId, Token $token = null)
    {
        $url = 'youtube/v3/playlists';
        $itemApiParts = 'snippet,status';

        return $this->requestYoutubeItem($url, $itemApiParts, $itemId, $token);
    }

    private function requestYoutubeItem($url, $parts, array $itemId, Token $token = null)
    {
        $itemKey = array_keys($itemId)[0];

        $query = $query = array(
            'part' => $parts,
            $itemKey => $itemId[$itemKey],
        );

        $response = $this->request($url, $query, $token);

        return $response;
    }
}
