<?php

namespace ApiConsumer\Images;

class ImageResponse
{
    protected $statusCode;
    protected $url;
    protected $type;
    protected $length;

    const MIN_SIZE = 10000;
    const MAX_SIZE = 200000;

    const MIN_RECOMMENDED_SIZE = 1000;
    const MAX_RECOMMENDED_SIZE = 1000000;

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

    public function isRecommended()
    {
        $code = $this->getStatusCode();
        $type = $this->getType();
        $length = $this->getLength();

        return 200 <= $code && $code < 300
            && strpos($type, 'image') !== false
            && self::MIN_RECOMMENDED_SIZE < $length && self::MAX_RECOMMENDED_SIZE > $length;

    }

    public function isValid()
    {
        $code = $this->getStatusCode();
        $type = $this->getType();
        $length = $this->getLength();

        return 200 <= $code && $code < 300
            && strpos($type, 'image') !== false
            && self::MIN_SIZE < $length && self::MAX_SIZE > $length;
    }

}