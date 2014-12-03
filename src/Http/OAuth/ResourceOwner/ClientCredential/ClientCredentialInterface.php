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
} 