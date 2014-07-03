<?php

namespace Provider;

use GuzzleHttp\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class GuzzleServiceProvider implements ServiceProviderInterface
{
    /**
     * Register Guzzle with Silex
     *
     * @param Application $app Application to register with
     */
    public function register(Application $app)
    {
        $app['guzzle.client'] = $app->share(function() use ($app) {
            return new Client();
        });
    }

    public function boot(Application $app)
    {
    }
}
