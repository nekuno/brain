<?php

namespace ApiConsumer\Exception;

use Exception;

class UrlChangedException extends \Exception
{
    protected $oldUrl;

    protected $newUrl;

    public function __construct($oldUrl, $newUrl, $message = "", $code = 0, Exception $previous = null)
    {
        $this->oldUrl = $oldUrl;
        $this->newUrl = $newUrl;
        $message = !empty($message) ? $message : sprintf('Url changed from %s to %s', $oldUrl, $newUrl);
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getOldUrl()
    {
        return $this->oldUrl;
    }

    /**
     * @param mixed $oldUrl
     */
    public function setOldUrl($oldUrl)
    {
        $this->oldUrl = $oldUrl;
    }

    /**
     * @return mixed
     */
    public function getNewUrl()
    {
        return $this->newUrl;
    }

    /**
     * @param mixed $newUrl
     */
    public function setNewUrl($newUrl)
    {
        $this->newUrl = $newUrl;
    }



}