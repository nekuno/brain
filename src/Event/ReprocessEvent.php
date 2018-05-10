<?php

namespace Event;

use Model\Link\Link;
use Symfony\Component\EventDispatcher\Event;

class ReprocessEvent extends Event
{

    protected $url;

    /**
     * @var Link[]
     */
    protected $links;

    protected $error;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function getUrl()
    {

        return $this->url;
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function setLinks($links)
    {
        $this->links = $links;
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
