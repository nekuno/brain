<?php

namespace Provider;

use ApiConsumer\ApiConsumer;
use ApiConsumer\Auth\DBUserProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ApiConsumerServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {

        $app->register(new \Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../ApiConsumer/config/apiConsumer.yml"));

        // User Provider
        $app['api_consumer.user_provider'] = $app->share(
            function ($app) {

                $userProvider = new DBUserProvider($app['dbs']['mysql_social']);

                return $userProvider;
            }
        );

        // Fetcher
        $app['api_consumer.fetcher'] = $app->share(
            function ($app) {

                $fetcher = new Fetcher($app['api_consumer.user_provider'], $app['guzzle.client'] ,$app['api_consumer.config']);

                return $fetcher;
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
