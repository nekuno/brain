<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\ResourceOwner\AbstractResourceOwnerTrait;

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
     * @var AbstractResourceOwnerTrait
     */
    protected $resourceOwner;

    /**
     * @var array
     */
    protected $user;

    /**
     * @param AbstractResourceOwnerTrait $resourceOwner
     */
    public function __construct(AbstractResourceOwnerTrait $resourceOwner)
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
