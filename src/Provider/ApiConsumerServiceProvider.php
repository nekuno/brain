<?php

namespace Provider;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Fetcher\FetcherService;
use ApiConsumer\Registry\Registry;
use ApiConsumer\Storage\DBStorage;
use Http\OAuth\Factory\ResourceOwnerFactory;
use Psr\Log\LoggerAwareInterface;
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

        // Resource Owners
        $app['api_consumer.resource_owner_factory'] = $app->share(
            function ($app) {

                $resourceOwnerFactory = new ResourceOwnerFactory($app['api_consumer.config']['resource_owner'], $app['guzzle.client'], $app['dispatcher']);

                return $resourceOwnerFactory;
            }
        );

        $app['api_consumer.resource_owner.google'] = $app->share(
            function ($app) {

                $resourceOwnerFactory = $app['api_consumer.resource_owner_factory'];
                /* @var $resourceOwnerFactory ResourceOwnerFactory */

                return $resourceOwnerFactory->build('google');
            }
        );

        $app['api_consumer.resource_owner.spotify'] = $app->share(
            function ($app) {

                $resourceOwnerFactory = $app['api_consumer.resource_owner_factory'];
                /* @var $resourceOwnerFactory ResourceOwnerFactory */

                return $resourceOwnerFactory->build('spotify');
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
                    $app['api_consumer.user_provider'],
                    $app['api_consumer.link_processor'],
                    $app['api_consumer.storage'],
                    $app['api_consumer.resource_owner_factory'],
                    $app['dispatcher'],
                    $app['api_consumer.config']['fetcher']
                );

                if ($fetcher instanceof LoggerAwareInterface) {
                    $fetcher->setLogger($app['monolog']);
                }

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
