<?php

namespace ApiConsumer\Exception;

use Exception;

class CannotProcessException extends \RuntimeException
{
    protected $url;
    protected $canScrape = true;

    public function __construct($url, $message = "", $code = 0, Exception $previous = null)
    {
        $this->url = $url;
        $message = !empty($message) ? $message : sprintf('Could not process url %s', $url);
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

    /**
     * @return bool
     */
    public function canScrape()
    {
        return $this->canScrape;
    }

    /**
     * @param bool $canScrape
     */
    public function setCanScrape($canScrape)
    {
        $this->canScrape = $canScrape;
    }

}