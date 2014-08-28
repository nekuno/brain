<?php

namespace Http\OAuth\Factory;

use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class ResourceOwnerFactory
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    public function __construct(array $options, Client $client, EventDispatcher $dispatcher)
    {
        $this->options = $options;
        $this->client = $client;
        $this->dispatcher = $dispatcher;
    }

    public function build($resourceOwner)
    {
        $options = $this->options[$resourceOwner];
        $resourceOwnerClass = $options['class'];
        $resourceOwner = new $resourceOwnerClass($this->client, $this->dispatcher, $options);

        return $resourceOwner;
    }
} 