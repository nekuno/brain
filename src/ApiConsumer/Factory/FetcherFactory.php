<?php

namespace ApiConsumer\Factory;

use ApiConsumer\Fetcher\FetcherInterface;


class FetcherFactory
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var ResourceOwnerFactory
     */
    protected $resourceOwnerFactory;

    public function __construct(array $options, ResourceOwnerFactory $resourceOwnerFactory)
    {

        $this->options = $options;
        $this->resourceOwnerFactory = $resourceOwnerFactory;
    }

    /**
     * @param $fetcherName
     * @return FetcherInterface
     */
    public function build($fetcherName)
    {
        $options = $this->options[$fetcherName];
        $fetcherClass = $options['class'];
        $resourceOwnerName = $options['resourceOwner'];
        $fetcher = new $fetcherClass($this->resourceOwnerFactory->build($resourceOwnerName));

        return $fetcher;
    }
}