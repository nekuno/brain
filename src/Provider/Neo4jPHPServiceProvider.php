<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/9/14
 * Time: 8:40 PM
 */

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

        if (getenv('APP_ENV') === 'prod') {
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