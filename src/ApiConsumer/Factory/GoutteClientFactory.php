<?php

namespace ApiConsumer\Factory;

use Goutte\Client;

class GoutteClientFactory
{
    public function build()
    {
        $client = new Client();
        $client->setMaxRedirects(10);

        $guzzleClient = $this->buildGuzzleClient();
        $client->setClient($guzzleClient);

        return $client;
    }

    protected function buildGuzzleClient()
    {
        $config = $this->buildGuzzleConfig();
        $guzzleClient = new \GuzzleHttp\Client($config);

        return $guzzleClient;
    }

    /**
     * TODO: Structure change when updating to Guzzle 5+ http://docs.guzzlephp.org/en/v5/request-options.html
     * @return array
     */
    protected function buildGuzzleConfig()
    {
        $defaultOptions = array(
            'timeout' => 10,
            'connect_timeout' => 10,
            'verify' => false
        );

        return $defaultOptions;
    }
}