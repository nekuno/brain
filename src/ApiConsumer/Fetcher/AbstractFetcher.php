<?php

namespace ApiConsumer\Fetcher;

use Http\OAuth\ResourceOwner\ResourceOwnerInterface;

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
     * @var ResourceOwnerInterface
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

    public function getUser()
    {
        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function fetchLinksFromUserFeed($user, $public);
}
