<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 29/06/14
 * Time: 1:37
 */

namespace Social\API\Consumer\Http;


use GuzzleHttp\Client as BaseClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

/**
 * Class Client
 * @package Social\API\Consumer\Http
 */
class Client {

    /**
     * @var BaseClient
     */
    private $client;

    public function __construct(BaseClient $client)
    {
        $this->client = $client;
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
    public function fetch($url, array $config = array(),  $legacy = false)
    {

        if($legacy){
            $oauth = new Oauth1([
                'consumer_key'    => $config['oauth_consumer_key'],
                'consumer_secret' => $config['oauth_consumer_secret'],
                'token'           => $config['oauth_access_token'],
                'token_secret'    => $config['oauth_access_token_secret']
            ]);

            $this->client->getEmitter()->attach($oauth);

            $response = $this->client->get($url, array('auth' => 'oauth'));
        } else {
            $response = $this->client->get($url);
        }

        try {
            $data = $response->json();
        } catch (RequestException $e) {
            throw $e;
        }

        return $data;
    }

    /**
     * @param $url
     */
    protected function getClient($url, $legacy = false)
    {

        if($legacy){

        }

    }

} 