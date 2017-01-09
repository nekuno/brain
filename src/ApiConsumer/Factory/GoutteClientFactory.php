<?php

namespace ApiConsumer\Factory;

use Goutte\Client;

class GoutteClientFactory
{
    public function build()
    {
        $client = new Client();
        $client->setMaxRedirects(10);

        return $client;
    }
}