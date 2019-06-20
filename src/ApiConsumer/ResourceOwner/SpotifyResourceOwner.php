<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\Exception\TokenException;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\SpotifyResourceOwner as SpotifyResourceOwnerBase;
use Model\Token\Token;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SpotifyResourceOwner extends SpotifyResourceOwnerBase
{
	use AbstractResourceOwnerTrait {
		AbstractResourceOwnerTrait::configureOptions as traitConfigureOptions;
		AbstractResourceOwnerTrait::__construct as private traitConstructor;
	}

    /** @var SpotifyUrlParser */
    protected $urlParser;

	public function __construct($httpClient, $httpUtils, $options, $name, $storage, $dispatcher, $APIStatusManager)
	{
		$this->traitConstructor($httpClient, $httpUtils, $options, $name, $storage, $dispatcher, $APIStatusManager);
	}

    public function canRequestAsClient()
    {
        return true;
    }

    public function requestAsClient($url, array $query = array())
    {
        $consumerKey = $this->getOption('consumer_key');
        $consumerSecret = $this->getOption('consumer_secret');

        $headers = array('Authorization' => 'Basic ' . base64_encode($consumerKey . ':' . $consumerSecret));
        $accessTokenUrl = $this->getOption('access_token_url');
        $response = $this->httpRequest($accessTokenUrl, 'grant_type=client_credentials', $headers, 'POST');
        $content = $this->getResponseContent($response);

        if (isset($content['access_token'])) {
            $clientToken = $content['access_token'];
            $url = $this->getOption('base_url') . $url;

            $headers = array();
            if (!empty($clientToken)) {
                $headers = array('Authorization' => 'Bearer ' . $clientToken);
            }

            $response = $this->httpRequest($this->normalizeUrl($url, $query), null, $headers);

            return $this->getResponseContent($response);
        }

       return array();
    }

    /**
	 * {@inheritDoc}
	 */
	protected function configureOptions(OptionsResolver $resolver)
	{
		$this->traitConfigureOptions($resolver);
		parent::configureOptions($resolver);

		$resolver->setDefaults(array(
			'base_url' => 'https://api.spotify.com/v1/',
		));

		$resolver->setDefined('redirect_uri');
	}

	/**
	 * {@inheritDoc}
	 */
	public function refreshAccessToken($token, array $extraParameters = array())
	{
		$refreshToken = $token['refreshToken'];
		$url = 'https://accounts.spotify.com/api/token';
		$authorization = base64_encode($this->options['consumer_key'] . ":" . $this->options['consumer_secret']);
		$headers = array('Authorization' => 'Basic ' . $authorization);
		$body = array(
			'grant_type' => 'refresh_token',
			'refresh_token' => $refreshToken,
		);

		$response = $this->httpRequest($this->normalizeUrl($url, $body), null, $headers, 'POST');

		return $this->getResponseContent($response);
	}

	public function requestTrack($trackId, Token $token = null)
    {
        $urlTrack = 'tracks/' . $trackId;
        $track = $this->request($urlTrack, array(), $token);

        return $track;
    }

    public function requestAlbum($albumId, Token $token = null)
    {
        $urlAlbum = 'albums/' . $albumId;
        $album = $this->request($urlAlbum, array(), $token);

        return $album;
    }

    public function requestArtist($artistId, Token $token = null)
    {
        $urlArtist = 'artists/' . $artistId;
        $artist = $this->request($urlArtist, array(), $token);

        return $artist;
    }

}
