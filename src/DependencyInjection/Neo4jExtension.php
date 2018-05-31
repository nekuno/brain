<?php

namespace DependencyInjection;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Transport;
use Model\Neo4j\GraphManager;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use DependencyInjection\Neo4jConfiguration as Configuration;

class Neo4jExtension extends Extension
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
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), $configs);

        $transportDefinition = new Definition(Transport::class, array(
            $config['host'],
            $config['port']
        ));

        $clientDefinition = new Definition(Client::class, array(
            $transportDefinition
        ));

        $graphManagerDefinition = new Definition(GraphManager::class, array(
            $clientDefinition
        ));

        $graphManagerDefinition->addMethodCall('setLogger', array(new Reference('monolog.logger')));

        $container->setDefinition('neo4j.graph_manager', $graphManagerDefinition);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'neo4j';
    }
}