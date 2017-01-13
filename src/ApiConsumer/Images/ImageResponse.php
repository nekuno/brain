<?php

namespace ApiConsumer\Images;

class ImageResponse
{
    protected $statusCode;
    protected $url;
    protected $type;
    protected $length;

    const MIN_SIZE = 1000;
    const MAX_SIZE = 1000000;

    const MIN_RECOMMENDED_SIZE = 10000;
    const MAX_RECOMMENDED_SIZE = 200000;

    /**
     * ImageResponse constructor.
     * @param $statusCode
     * @param $url
     * @param $type
     * @param $length
     */
    public function __construct($url, $statusCode = null, $type = null, $length = null)
    {
        $this->statusCode = $statusCode;
        $this->url = $url;
        $this->type = $type;
        $this->length = $length;
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
        return $this->isImage() && $this->hasRecommendedLength();
    }

    public function isValid()
    {
        return $this->isImage() && $this->hasValidLength();
    }

    public function isImage()
    {
        $code = $this->getStatusCode();
        $type = $this->getType();

        return (200 <= $code && $code < 300) && strpos($type, 'image') !== false;
    }

    private function hasValidLength()
    {
        $length = $this->getLength();

        return self::MIN_SIZE < $length && self::MAX_SIZE > $length;
    }

    private function hasRecommendedLength()
    {
        $length = $this->getLength();

        return self::MIN_RECOMMENDED_SIZE < $length && self::MAX_RECOMMENDED_SIZE > $length;
    }

}