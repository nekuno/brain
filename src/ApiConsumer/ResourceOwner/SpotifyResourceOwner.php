<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\SpotifyResourceOwner as SpotifyResourceOwnerBase;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Buzz\Message\RequestInterface as HttpRequestInterface;


class SpotifyResourceOwner extends SpotifyResourceOwnerBase
{
	use AbstractResourceOwnerTrait {
		AbstractResourceOwnerTrait::configureOptions as traitConfigureOptions;
		AbstractResourceOwnerTrait::addOauthData as traitAddOauthData;
		AbstractResourceOwnerTrait::__construct as private traitConstructor;
	}

    /** @var SpotifyUrlParser */
    protected $urlParser;

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
		$headers = array('Authorization: Basic ' . $authorization);
		$body = array(
			'grant_type' => 'refresh_token',
			'refresh_token' => $refreshToken,
		);

		$response = $this->httpRequest($this->normalizeUrl($url, $body), null, $headers, HttpRequestInterface::METHOD_POST);

		return $this->getResponseContent($response);
	}

	protected function addOauthData($data, $token)
	{
		$newToken = $this->traitAddOauthData($data, $token);
		if (!isset($newToken['refreshToken']) && isset($token['refreshToken'])){
			$newToken['refreshToken'] = $token['refreshToken'];
		}
		return $newToken;
	}

	public function requestTrack($trackId)
    {
        $urlTrack = 'tracks/' . $trackId;
        $track = $this->authorizedAPIRequest($urlTrack, array());

        return $track;
    }

    public function requestAlbum($albumId)
    {
        $urlAlbum = 'albums/' . $albumId;
        $album = $this->authorizedAPIRequest($urlAlbum, array());

        return $album;
    }

    public function requestArtist($artistId)
    {
        $urlArtist = 'artists/' . $artistId;
        $artist = $this->authorizedAPIRequest($urlArtist, array());

        return $artist;
    }

}
