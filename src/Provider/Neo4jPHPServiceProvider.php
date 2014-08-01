<?php

namespace Provider;

use Everyman\Neo4j\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class Neo4jPHPServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {

        // Initialize neo4j
        $app['neo4j.client'] = $app->share(
            function ($app) {

                $client = new Client($app['neo4j.options']['host'], $app['neo4j.options']['port']);
                
                if (isset($app['neo4j.options']['auth']) && $app['neo4j.options']['auth']) {
                    $client
                        ->getTransport()
                        ->setAuth($app['neo4j.options']['user'], $app['neo4j.options']['pass']);
                }

                return $client;
            }
        );
    }

    /**
     * { @inheritdoc }
     */
    public function boot(Application $app)
    {

    }

}
