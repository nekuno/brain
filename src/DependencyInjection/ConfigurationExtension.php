<?php

namespace DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class ConfigurationExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $fields = Yaml::parseFile(__DIR__.'/../../config/fields.yaml');
        $container->setParameter('fields', $fields);
        $container->setParameter('profile_fields', $fields['profile']);

        $configMetadata = Yaml::parseFile(__DIR__.'/../../config/config_metadata.yaml');
        $container->setParameter('metadata_config', $configMetadata['metadata.config']);

        $configValidator = Yaml::parseFile(__DIR__.'/../../config/config_validator.yaml');
        $container->setParameter('validator_config', $configValidator['validator.config']);
    }
}
