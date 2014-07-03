<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 1:30 PM
 */

namespace Social\API\Consumer;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Social\API\Consumer\Auth\UserProviderInterface;
use Social\API\Consumer\Storage\StorageInterface;

class AbstractConsumer
{

    /** @var StorageInterface */
    protected $storage;

    /** @var UserProviderInterface */
    protected $userProvider;

    /** @var Client */
    protected $httpClient;

    /** @var array Configuration */
    protected $options = array();

    public function __construct(StorageInterface $store, UserProviderInterface $userProvider, Client $httpClient, array $options = array())
    {

        $this->storage = $store;

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
    public function fetch($url, array $config = array(), $legacy = false)
    {

        if ($legacy) {
            $oauth = new Oauth1([
                'consumer_key'    => $config['oauth_consumer_key'],
                'consumer_secret' => $config['oauth_consumer_secret'],
                'token'           => $config['oauth_access_token'],
                'token_secret'    => $config['oauth_access_token_secret']
            ]);

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

    /**
     * @param $data
     * @return array
     * @throws \Exception
     */
    protected function processData($data)
    {
        $links = array();
        foreach ($data as $userId => $shared) {
            try {
                $parseLinks = $this->parseLinks($userId, $shared);
                $links      = $links + $parseLinks;
            } catch (\Exception $e) {
                throw $e;
            }
        }

        try {
            return $this->storage->storeLinks($links);
        } catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * Parse links to model expected format
     *
     * @param $data
     * @param $userId
     * @return mixed
     */
    protected function parseLinks($userId, array $data = array()){
        return array();
    }

    /**
     * @param $e
     * @return string
     */
    protected function getError(\Exception $e)
    {
        return sprintf('Error: %s on file %s line %s', $e->getMessage(), $e->getFile(), $e->getLine());
    }

} 
