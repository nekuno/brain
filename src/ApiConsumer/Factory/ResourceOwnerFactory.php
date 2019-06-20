<?php

namespace ApiConsumer\Factory;

use ApiConsumer\APIStatusManager;
use Http\Client\HttpClient;
use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\HttpUtils;


class ResourceOwnerFactory
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var HttpClient
     */
    protected $client;

	/**
	 * @var HttpUtils
	 */
	protected $httpUtils;

	/**
	 * @var RequestDataStorageInterface
	 */
	protected $storage;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var APIStatusManager
     */
    protected $APIStatusManager;

    public function __construct(array $options, HttpClient $client, HttpUtils $httpUtils, RequestDataStorageInterface $storage, EventDispatcherInterface $dispatcher, APIStatusManager $APIStatusManager)
    {
        $this->options = $options;
        $this->client = $client;
        $this->httpUtils = $httpUtils;
        $this->storage = $storage;
        $this->dispatcher = $dispatcher;
        $this->APIStatusManager = $APIStatusManager;
    }

    /**
     * @param $resourceOwnerName
     * @return ResourceOwnerInterface
     */
    public function build($resourceOwnerName)
    {
        $options = $this->options[$resourceOwnerName];
        $resourceOwnerClass = $options['class'];
        $resourceOwner = new $resourceOwnerClass($this->client, $this->httpUtils, $options, $resourceOwnerName, $this->storage, $this->dispatcher, $this->APIStatusManager);

        return $resourceOwner;
    }
}