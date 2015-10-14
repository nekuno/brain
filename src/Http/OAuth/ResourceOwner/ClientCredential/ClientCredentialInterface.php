<?php

namespace Http\OAuth\ResourceOwner\ClientCredential;


interface ClientCredentialInterface
{
    /**
     * Get a client token
     *
     * @return string
     */
    public function getClientToken();

    /**
     * Get an application key
     *
     * @return string
     */
    public function getApplicationToken();
} 