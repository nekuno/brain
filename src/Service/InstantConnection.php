<?php

namespace Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class InstantConnection
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct($instantHost, $instantApiSecret)
    {
        $config = array(
            'base_uri' => $instantHost,
            'auth' => array('brain', $instantApiSecret)
        );

        $this->client = new Client($config);
    }

    public function fetchStart($data)
    {
        $url = 'api/fetch/start';
        return $this->post($url, $data);
    }

    public function fetchFinish($data)
    {
        $url = 'api/fetch/finish';
        return $this->post($url, $data);
    }

    public function processStart($data)
    {
        $url = 'api/process/start';
        return $this->post($url, $data);
    }

    public function processLink($data)
    {
        $url = 'api/process/link';
        return $this->post($url, $data);
    }

    public function processFinish($data)
    {
        $url = 'api/process/finish';
        return $this->post($url, $data);
    }

    public function similarityStart($data)
    {
        $url = 'api/similarity/start';
        return $this->post($url, $data);
    }

    public function similarityStep($data)
    {
        $url = 'api/similarity/step';
        return $this->post($url, $data);
    }

    public function similarityFinish($data)
    {
        $url = 'api/similarity/finish';
        return $this->post($url, $data);
    }

    public function matchingStart($data)
    {
        $url = 'api/matching/start';
        return $this->post($url, $data);
    }

    public function matchingStep($data)
    {
        $url = 'api/matching/step';
        return $this->post($url, $data);
    }

    public function matchingFinish($data)
    {
        $url = 'api/matching/finish';
        return $this->post($url, $data);
    }
    public function affinityStart($data)
    {
        $url = 'api/affinity/start';
        return $this->post($url, $data);
    }

    public function affinityStep($data)
    {
        $url = 'api/affinity/step';
        return $this->post($url, $data);
    }

    public function affinityFinish($data)
    {
        $url = 'api/affinity/finish';
        return $this->post($url, $data);
    }

    public function clearUser($data)
    {
        $url = 'api/user/clear';
        return $this->post($url, $data);
    }

    public function setStatus($data)
    {
        $url = 'api/user/status';
        return $this->post($url, $data);
    }

    public function sendMessage($data)
    {
        $url = 'api/message';
        return $this->post($url, $data);
    }

    public function deleteMessages($data)
    {
        $url = 'api/user/messages';
        return $this->delete($url, $data);
    }

    protected function post($url, $data)
    {
        $data = array('json' => $data);

        try{
            return $this->client->post($url, $data);
        } catch (RequestException $e) {
            return null;
        }
    }

    protected function delete($url, $data)
    {
        $data = array('json' => $data);

        try{
            return $this->client->delete($url, $data);
        } catch (RequestException $e) {
            return null;
        }
    }

}