<?php

namespace ApiConsumer\ResourceOwner;

use ApiConsumer\Exception\TokenException;
use ApiConsumer\LinkProcessor\UrlParser\SteamUrlParser;
use Buzz\Exception\RequestException;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\AbstractResourceOwner;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Model\Token\Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class SteamResourceOwner extends AbstractResourceOwner
{
    use AbstractResourceOwnerTrait {
        AbstractResourceOwnerTrait::configureOptions as traitConfigureOptions;
        AbstractResourceOwnerTrait::__construct as private traitConstructor;
    }

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
        $clientToken = $this->getOption('client_credential')['application_token'];
        $url = $this->getOption('base_url') . $url;
        $query = array_merge($query, array(
            'key' => $clientToken,
        ));

        $response = $this->httpRequest($this->normalizeUrl($url, $query));
        $content = $this->getResponseContent($response);

        return is_array($content) ? $content : array();
    }

    public function requestAsUser($url, array $query = array(), Token $token = null)
    {
        $clientToken = $this->getOption('client_credential')['application_token'];
        $url = $this->getOption('base_url') . $url;
        $query = array_merge($query, array(
            'key' => $clientToken,
            'steamid' => $this->getOpenId($token),
        ));

        $response = $this->httpRequest($this->normalizeUrl($url, $query));
        $content = $this->getResponseContent($response);

        return is_array($content) ? $content : array();
    }

    protected function getOpenId(Token $token)
    {
        $openId = $token->getResourceId();
        if (!$openId) {
            throw new TokenException('OpenId not found');
        }

        return $openId;
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
                'base_url' => 'https://api.steampowered.com/',
                'infos_url' => null,
                'access_token_url' => null,
                'authorization_url' => null
            )
        );

        $resolver->setDefined('redirect_uri');
    }

    public function refreshAccessToken($token, array $extraParameters = array())
    {
    }

    public function requestGame($gameId)
    {
        $url = "ISteamUserStats/GetSchemaForGame/v2";
        $query = array('appid' => $gameId);

        return $this->requestAsClient($url, $query);
    }

    public function requestGameImage($appId)
    {
        try {
            $url = "https://steamcdn-a.akamaihd.net/steam/apps/$appId/header.jpg";
            $response = $this->httpRequest($url);
            $content = $this->getResponseContent($response);
        } catch (RequestException $e) {
            return SteamUrlParser::DEFAULT_IMAGE_PATH;
        }

        return isset($content['errors']) ? SteamUrlParser::DEFAULT_IMAGE_PATH : $url;
    }

    /**
     * @param string $url
     * @param array $parameters
     *
     * @return HttpResponse
     */
    protected function doGetTokenRequest($url, array $parameters = array())
    {
    }

    /**
     * @param string $url
     * @param array $parameters
     *
     * @return HttpResponse
     */
    protected function doGetUserInformationRequest($url, array $parameters = array())
    {
    }

    /**
     * Retrieves the user's information from an access_token
     *
     * @param array $accessToken The access token
     * @param array $extraParameters An array of parameters to add to the url
     *
     * @return UserResponseInterface The wrapped response interface.
     */
    public function getUserInformation(array $accessToken, array $extraParameters = array())
    {
    }

    /**
     * Returns the provider's authorization url
     *
     * @param string $redirectUri The uri to redirect the client back to
     * @param array $extraParameters An array of parameters to add to the url
     *
     * @return string The authorization url
     */
    public function getAuthorizationUrl($redirectUri, array $extraParameters = array())
    {
    }

    /**
     * Retrieve an access token for a given code
     *
     * @param Request $request The request object where is going to extract the code from
     * @param string $redirectUri The uri to redirect the client back to
     * @param array $extraParameters An array of parameters to add to the url
     *
     * @return array The access token
     */
    public function getAccessToken(Request $request, $redirectUri, array $extraParameters = array())
    {
    }

    /**
     * Check whatever CSRF token from request is valid or not
     *
     * @param string $csrfToken
     *
     * @return boolean True if CSRF token is valid
     *
     * @throws AuthenticationException When token is not valid
     */
    public function isCsrfTokenValid($csrfToken)
    {
    }

    /**
     * Checks whether the class can handle the request.
     *
     * @param Request $request
     *
     * @return boolean
     */
    public function handles(Request $request)
    {
    }
}
