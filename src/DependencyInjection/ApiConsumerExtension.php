<?php

namespace DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class ApiConsumerExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     * @throws \RuntimeException
     * @throws InvalidConfigurationException
     * @throws \Symfony\Component\DependencyInjection\Exception\BadMethodCallException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\OutOfBoundsException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $apiConsumerConfig = Yaml::parseFile(__DIR__.'/../ApiConsumer/config/apiConsumer.yml');
        $container->setParameter('api_consumer_config', $apiConsumerConfig['api_consumer.config']);
        $container->setParameter('api_consumer_fetchers', $apiConsumerConfig['api_consumer.config']['fetcher']);
        $container->setParameter('api_consumer_resource_owners', $apiConsumerConfig['api_consumer.config']['resource_owner']);
        $container->setParameter('api_consumer_resource_processors', $apiConsumerConfig['api_consumer.config']['processor']);
    }
}
