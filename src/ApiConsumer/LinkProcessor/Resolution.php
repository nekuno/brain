<?php

namespace ApiConsumer\LinkProcessor;

class Resolution
{
    protected $startingUrl;
    protected $finalUrl;
    protected $statusCode;

    /**
     * @return mixed
     */
    public function getStartingUrl()
    {
        return $this->startingUrl;
    }

    /**
     * @param mixed $startingUrl
     */
    public function setStartingUrl($startingUrl)
    {
        $this->startingUrl = $startingUrl;
    }

    /**
     * @return mixed
     */
    public function getFinalUrl()
    {
        return $this->finalUrl;
    }

    /**
     * @param mixed $finalUrl
     */
    public function setFinalUrl($finalUrl)
    {
        $this->finalUrl = $finalUrl;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    public function isCorrect()
    {
        return $this->getStatusCode() && $this->getStatusCode() < 300;
    }

    public function didUrlChange()
    {
        return $this->getStartingUrl() != $this->getFinalUrl();
    }


}