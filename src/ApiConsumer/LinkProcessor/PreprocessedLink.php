<?php

namespace ApiConsumer\LinkProcessor;


use Model\Link\Link;
use Model\Token\Token;

class PreprocessedLink
{
    protected $url;

    /**
     * @var $exceptions \Exception[]
     */
    protected $exceptions = array();

    /**
     * @var string
     */
    protected $resourceItemId;

    /**
     * @var Link[]
     */
    protected $links;

    /**
     * @var Token which was used for fetching
     */
    protected $token;

    protected $source;

    /**
     * @var $type string Extra subtype from fetching data
     */
    protected $type;

    protected $synonymousParameters;

    /**
     * PreprocessedLink constructor.
     * @param $url
     */
    public function __construct($url)
    {
        $this->url = $url;

        $this->links = array(new Link());
        $this->synonymousParameters = new SynonymousParameters();
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
    public function getResourceItemId()
    {
        return $this->resourceItemId;
    }

    /**
     * @param string $resourceItemId
     */
    public function setResourceItemId($resourceItemId)
    {
        $this->resourceItemId = $resourceItemId;
    }

    /**
     * @return Link
     */
    public function getFirstLink()
    {
        return reset($this->links);
    }

    /**
     * @param Link $link
     */
    public function setFirstLink($link)
    {
        $this->links[0] = $link;
    }

    /**
     * @return Link[]
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param Link[] $links
     */
    public function setLinks($links)
    {
        $this->links = $links;
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param Token $token
     */
    public function setToken(Token $token = null)
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
     * @return SynonymousParameters
     */
    public function getSynonymousParameters()
    {
        return $this->synonymousParameters;
    }

    /**
     * @param SynonymousParameters $synonymousParameters
     */
    public function setSynonymousParameters($synonymousParameters)
    {
        $this->synonymousParameters = $synonymousParameters;
    }



}