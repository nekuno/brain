<?php

namespace ApiConsumer\Fetcher;

class Fetcher
{
    /** @var UserProviderInterface */
    protected $userProvider;

    /** @var Client */
    protected $httpClient;
    
    /** @var array Configuration */
    protected $config = array();

    /**
     * @param UserProviderInterface $userProvider
     * @param Client $httpClient
     * @param array $options
     */
    public function __construct(UserProviderInterface $userProvider, Client $httpClient, array $config = array())
    {

        $this->userProvider = $userProvider;

        $this->httpClient = $httpClient;

        $this->config = array_merge($this->config, $config);
    }
}