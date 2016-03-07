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

    /**
     * @var array Token which was used for fetching
     */
    protected $token = array();

    protected $source;

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
        return (int)$this->statusCode;
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
     * @param \Exception $e
     */
    public function addException(\Exception $e)
    {
        $this->exceptions[] = $e;
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

    public function addToLink($array)
    {
        foreach ($array as $key=>$value)
        {
            $this->link[$key] = $value;
        }

        return $this->getLink();
    }

    /**
     * TODO: Refactor to Link object whenever available
     * @param $label
     */
    public function addAdditionalLabel($label)
    {
        if (!isset($this->link['additionalLabels'])){
            $this->link['additionalLabels'] = array();
        }

        $this->link['additionalLabels'][] = $label;
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

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }



}