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

/**
 * Class AbstractConsumer
 *
 * @package ApiConsumer\Restful\Consumer
 */
abstract class AbstractConsumer
{

    /** @var UserProviderInterface */
    protected $userProvider;

    /** @var Client */
    protected $httpClient;

    /** @var array Configuration */
    protected $options = array();

    /**
     * @param UserProviderInterface $userProvider
     * @param Client $httpClient
     * @param array $options
     */
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
     * @throws \GuzzleHttp\Exception\RequestException
     * @return mixed
     */
    public function makeRequestJSON($url)
    {

        if (isset($this->options['legacy']) && $this->options['legacy'] === true) {
            $oauth = new Oauth1(
                [
                    'consumer_key'    => $this->options['oauth_consumer_key'],
                    'consumer_secret' => $this->options['oauth_consumer_secret'],
                    'token'           => $this->options['oauth_access_token'],
                    'token_secret'    => $this->options['oauth_access_token_secret']
                ]
            );

            $this->httpClient->getEmitter()->attach($oauth);

            $response = $this->httpClient->get($url, array('auth' => 'oauth'));
        } else {
            $clientConfig = isset($this->options['headers'])?array('headers' => $this->options['headers']):array();
            $response = $this->httpClient->get($url, $clientConfig);
        }

        try {
            $data = $response->json();
        } catch (RequestException $e) {
            throw $e;
        }

        return $data;
    }
}
