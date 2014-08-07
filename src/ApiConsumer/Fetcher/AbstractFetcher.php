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
    * @var \Http\OAuth\ResourceOwner\resourceOwnerInterface
    */
    protected $resourceOwner;

    /**
    * @var array
    */
    protected $user;

    /**
     * @param UserProviderInterface $userProvider
     * @param Client $httpClient
     * @param array $options
     */
    public function __construct(ResourceOwnerInterface $resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceOwnerName()
    {
        return $this->resourceOwner->getName();
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
     * Fetch links from user feed
     *
     * @param $url
     * @return mixed
     */
    public function makeRequestJSON($url, array $query=array())
    {
        return $this->resourceOwner->authorizedHttpRequest($url, $query, $this->user);
    }

    /**
     * {@inheritDoc}
     */
    abstract public function fetchLinksFromUserFeed($user);
}
