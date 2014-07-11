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

                $client = new Client($app['db.neo4j.host'], $app['db.neo4j.port']);

                return $client;
            }
        );

        if (isset($app['db.neo4j.auth']) && $app['db.neo4j.auth']) {
            $app['neo4j.client'] = $app->extend(
                'neo4j.client',
                function ($client, $app) {

                    $client
                        ->getTransport()
                        ->setAuth($app['db.neo4j.user'], $app['db.neo4j.pass']);

                    return $client;
                }
            );
        }
    }

    /**
     * { @inheritdoc }
     */
    public function boot(Application $app)
    {

    }

}
