<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 1:30 PM
 */

namespace ApiConsumer\Restful\Consumer;

use ApiConsumer\Auth\UserProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class AbstractConsumer
{

    /** @var UserProviderInterface */
    protected $userProvider;

    /** @var Client */
    protected $httpClient;

    /** @var array Configuration */
    protected $options = array();

    public function __construct(UserProviderInterface $userProvider, Client $httpClient, array $options = array())
    {

        $this->userProvider = $userProvider;

        $this->httpClient = $httpClient;

        $this->options = array_merge($this->options, $options);
    }

    /**
     * Fetch last links from user feed on Facebook
     *
     * @param $url
     * @param $config array
     * @param $legacy boolean true if Oauth version 1
     * @return mixed
     * @throws RequestException
     */
    public function makeRequestJSON($url, array $config = array(), $legacy = false)
    {

        if ($legacy) {
            $oauth = new Oauth1(
                [
                    'consumer_key'    => $config['oauth_consumer_key'],
                    'consumer_secret' => $config['oauth_consumer_secret'],
                    'token'           => $config['oauth_access_token'],
                    'token_secret'    => $config['oauth_access_token_secret']
                ]
            );

            $this->httpClient->getEmitter()->attach($oauth);

            $response = $this->httpClient->get($url, array('auth' => 'oauth'));
        } else {
            $response = $this->httpClient->get($url);
        }

        try {
            $data = $response->json();
        } catch (RequestException $e) {
            throw $e;
        }

        return $data;
    }
}
