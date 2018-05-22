<?php

namespace Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class InstantConnection
{
    protected $client;

    /**
     * InstantConnection constructor.
     * @param $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
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

    public function clearUser($data)
    {
        $url = 'api/user/clear';
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