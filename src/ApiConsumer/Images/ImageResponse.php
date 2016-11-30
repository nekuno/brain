<?php

namespace ApiConsumer\Images;

class ImageResponse
{
    protected $statusCode;
    protected $url;
    protected $type;
    protected $length;

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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param mixed $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    public function isValid()
    {
        $code = $this->getStatusCode();
        $type = $this->getType();
        $length = $this->getLength();

        return 200 <= $code && $code < 300 //status code
            && strpos($type, 'image') !== false  //image type
            && 10000 < $length && 200000 > $length;  //image size 10K - 200K

    }

}