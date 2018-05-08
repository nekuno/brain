<?php

namespace DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Neo4jConfiguration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder $builder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $rootNode = $builder->root('neo4j');
        $rootNode->children()
            ->scalarNode('host')->isRequired()->end()
            ->scalarNode('port')->isRequired()->end()
            ->scalarNode('user')->isRequired()->end()
            ->scalarNode('pass')->isRequired()->end()
        ->end();

        return $builder;
    }
}
