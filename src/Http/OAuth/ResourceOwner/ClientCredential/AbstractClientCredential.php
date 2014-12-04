<?php

namespace Http\OAuth\ResourceOwner\ClientCredential;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractClientCredential implements ClientCredentialInterface
{
    protected $options;

    public function __construct($options)
    {
        // Resolve merged options
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);
        $this->options = $options;
    }

    /**
     * Get the value of an option
     *
     * @param $name the name of the options
     */
    public function getOption($name)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new \InvalidArgumentException(sprintf('Unknown option "%s"', $name));
        }

        return $this->options[$name];
    }

    /**
     * Configure the option resolver
     *
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {

    }

    /**
     * {@inheritDoc}
     */
    abstract public function getClientToken();
} 