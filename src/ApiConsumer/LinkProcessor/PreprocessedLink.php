<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace ApiConsumer\LinkProcessor;


class PreprocessedLink
{
    protected $fetched;

    protected $canonical;

    /**
     * @var $history \Symfony\Component\BrowserKit\History
     *  */
    protected $history;

    protected $statusCode;

    /**
     * @var $exceptions \Exception[]
     */
    protected $exceptions = array();

    protected $type = 'Link';

    /**
     * @var $additional array ('relation'=> string, 'link' => PreprocessedLink)
     */
    protected $additional = array();

    protected $link = array();

    protected $token = array();

    /**
     * PreprocessedLink constructor.
     * @param $fetched
     */
    public function __construct($fetched)
    {
        $this->fetched = $fetched;
    }

    /**
     * @return mixed
     */
    public function getFetched()
    {
        return $this->fetched;
    }

    /**
     * @param mixed $fetched
     */
    public function setFetched($fetched)
    {
        $this->fetched = $fetched;
    }

    /**
     * @return mixed
     */
    public function getCanonical()
    {
        return $this->canonical;
    }

    /**
     * @param mixed $canonical
     */
    public function setCanonical($canonical)
    {
        $this->canonical = $canonical;
    }

    /**
     * @return \Symfony\Component\BrowserKit\History
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @param \Symfony\Component\BrowserKit\History $history
     */
    public function setHistory($history)
    {
        $this->history = $history;
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
     * @return \Exception[]
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * @param \Exception[] $exceptions
     */
    public function setExceptions($exceptions)
    {
        $this->exceptions = $exceptions;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return PreprocessedLink[]
     */
    public function getAdditional()
    {
        return $this->additional;
    }

    /**
     * @param PreprocessedLink[] $additional
     */
    public function setAdditional($additional)
    {
        $this->additional = $additional;
    }

    /**
     * @return array
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param array $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

}