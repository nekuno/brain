<?php

namespace Event;

use Symfony\Component\EventDispatcher\Event;

class CheckEvent extends Event
{

    protected $url;

    protected $response;

    protected $error;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function getUrl()
    {

        return $this->url;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setError($error)
    {
        $this->error = $error;
    }
}
