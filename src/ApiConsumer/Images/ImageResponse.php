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
    public function getUrl()
    {
        return $this->url;
    }

    public function isValid()
    {
        return $this->isImage() && $this->hasValidLength();
    }

    public function isImage()
    {
        $code = $this->statusCode;
        $type = $this->type;

        return (200 <= $code && $code < 300) && strpos($type, 'image') !== false;
    }

    private function hasValidLength()
    {
        $length = $this->length;

        return self::MIN_SIZE < $length && self::MAX_SIZE > $length;
    }

    public function toArray()
    {
        return array(
            'statusCode' => $this->statusCode,
            'url' => $this->url,
            'type' => $this->type,
            'length' => $this->length,
        );
    }
}