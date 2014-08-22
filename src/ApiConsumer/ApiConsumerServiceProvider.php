<?php

namespace ApiConsumer;

use ApiConsumer\Fetcher\FetcherService;
use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Event\EventManager;
use ApiConsumer\Registry\Registry;
use ApiConsumer\Storage\DBStorage;
use ApiConsumer\Listener\OAuthTokenSubscriber;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ApiConsumerServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {

        $app->register(new \Igorw\Silex\ConfigServiceProvider(__DIR__ . "/config/apiConsumer.yml"));

        // User Provider
        $app['api_consumer.user_provider'] = $app->share(
            function ($app) {

                $userProvider = new DBUserProvider($app['dbs']['mysql_social']);

                return $userProvider;
            }
        );

        // Resource Owners
        $app['api_consumer.get_resource_owner_by_name'] = $app->protect(
            function ($name) use ($app) {

                $options = $app['api_consumer.config']['resource_owner'][$name];
                $resourceOwnerClass = $options['class'];
                $resourceOwner = new $resourceOwnerClass($app['guzzle.client'], $app['dispatcher'], $options);

                return $resourceOwner;
            }
        );

        $app['api_consumer.resource_owner.google'] = $app->share(
            function ($app) {
                $getResourceOwnerByName = $app['api_consumer.get_resource_owner_by_name'];
                return $getResourceOwnerByName('google');
            }
        );

        $app['api_consumer.resource_owner.spotify'] = $app->share(
            function ($app) {
                $getResourceOwnerByName = $app['api_consumer.get_resource_owner_by_name'];
                return $getResourceOwnerByName('spotify');
            }
        );

        //Registry
        $app['api_consumer.registry'] = $app->share(
            function ($app) {

                $registry = new Registry($app['orm.ems']['mysql_brain']);

                return $registry;
            }
        );

        //Storage
        $app['api_consumer.storage'] = $app->share(
            function ($app) {

                $storage = new DBStorage($app['links.model']);

                return $storage;
            }
        );

        // Fetcher Service
        $app['api_consumer.fetcher'] = $app->share(
            function ($app) {

                $fetcher = new FetcherService(
                    $app['monolog'],
                    $app['api_consumer.user_provider'],
                    $app['api_consumer.registry'],
                    $app['api_consumer.storage'],
                    $app['api_consumer.get_resource_owner_by_name'],
                    $app['api_consumer.config']['fetcher']
                );

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
