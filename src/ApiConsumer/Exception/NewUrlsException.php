<?php

namespace ApiConsumer\Exception;

class NewUrlsException extends \Exception
{
    protected $newUrls = array();

    /**
     * NewUrlsException constructor.
     * @param array $newUrls
     */
    public function __construct(array $newUrls)
    {
        $this->newUrls = $newUrls;
    }

    /**
     * @return array
     */
    public function getNewUrls()
    {
        return $this->newUrls;
    }

    /**
     * @param array $newUrls
     */
    public function setNewUrls($newUrls)
    {
        $this->newUrls = $newUrls;
    }



}