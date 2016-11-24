<?php

namespace ApiConsumer\Exception;

use Exception;

class CouldNotResolveException extends \Exception
{
    protected $url;

    public function __construct($url, $message = "", $code = 0, Exception $previous = null)
    {
        $this->url = $url;
        $message = !empty($message) ? $message : sprintf('Could not resolve url %s', $url);
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }


}