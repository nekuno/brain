<?php

namespace Http\OAuth\ResourceOwner\ClientCredential;


abstract class AbstractClientCredential implements ClientCredentialInterface
{
    protected $options;

    public function __construct($options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function getClientToken();
} 