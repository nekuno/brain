<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\ResourceOwner\AbstractResourceOwnerTrait;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use Model\Token\Token;

abstract class AbstractFetcher implements FetcherInterface
{
    /**
     * @var string
     */
    protected $url = '';

    /**
     * @var array
     */
    protected $rawFeed = array();

    /**
     * @var ResourceOwnerInterface|AbstractResourceOwnerTrait
     */
    protected $resourceOwner;

    /**
     * @var Token
     */
    protected $token;

    /**
     * @param ResourceOwnerInterface $resourceOwner
     */
    public function __construct(ResourceOwnerInterface $resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }


    public function getToken()
    {
        return $this->token;
    }

    public function setToken(Token $token)
    {
        $this->token = $token;
    }
}
