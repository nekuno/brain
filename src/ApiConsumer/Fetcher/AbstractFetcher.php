<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\ResourceOwner\AbstractResourceOwnerTrait;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;

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
     * @var array
     */
    protected $user;

    /**
     * @param ResourceOwnerInterface $resourceOwner
     */
    public function __construct(ResourceOwnerInterface $resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get query
     *
     * @return array
     */
    protected function getQuery()
    {
        return array();
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function fetchLinksFromUserFeed($user, $public);
}
