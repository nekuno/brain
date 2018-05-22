<?php

namespace ApiConsumer\Factory;

use Buzz\Client\Curl;
use Http\Client\HttpClient;
use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use Service\EventDispatcher;
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
     * @var EventDispatcher
     */
    protected $dispatcher;

    public function __construct(array $options, HttpClient $client, HttpUtils $httpUtils, RequestDataStorageInterface $storage, EventDispatcher $dispatcher)
    {
        $this->options = $options;
        $this->client = $client;
        $this->httpUtils = $httpUtils;
        $this->storage = $storage;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param $resourceOwnerName
     * @return ResourceOwnerInterface
     */
    public function build($resourceOwnerName)
    {
        $options = $this->options[$resourceOwnerName];
        $resourceOwnerClass = $options['class'];
        $resourceOwner = new $resourceOwnerClass($this->client, $this->httpUtils, $options, $resourceOwnerName, $this->storage, $this->dispatcher);

        return $resourceOwner;
    }
}