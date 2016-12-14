<?php

namespace Provider;

use ApiConsumer\Factory\FetcherFactory;
use ApiConsumer\Factory\ProcessorFactory;
use ApiConsumer\Fetcher\FetcherService;
use ApiConsumer\Fetcher\GetOldTweets\GetOldTweets;
use ApiConsumer\Fetcher\ProcessorService;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\Registry\Registry;
use ApiConsumer\Factory\ResourceOwnerFactory;
use Igorw\Silex\ConfigServiceProvider;
use ApiConsumer\Fetcher\GetOldTweets\TweetManager;
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

        $app->register(new ConfigServiceProvider(__DIR__ . "/../ApiConsumer/config/apiConsumer.yml"));

        // Resource Owners
        $app['api_consumer.resource_owner_factory'] = $app->share(
            function ($app) {

                $resourceOwnerFactory = new ResourceOwnerFactory($app['api_consumer.config']['resource_owner'], $app['hwi_oauth.http_client'], $app['security.http_utils'], $app['hwi_oauth.storage.session'], $app['dispatcher']);

                return $resourceOwnerFactory;
            }
        );

        //Registry
        $app['api_consumer.registry'] = $app->share(
            function ($app) {

                $registry = new Registry($app['orm.ems']['mysql_brain']);

                return $registry;
            }
        );

        // Fetcher Service
        $app['api_consumer.fetcher_factory'] = $app->share(
            function ($app) {

                $resourceOwnerFactory = new FetcherFactory($app['api_consumer.config']['fetcher'], $app['api_consumer.resource_owner_factory']);

                return $resourceOwnerFactory;
            }
        );

        $app['api_consumer.processor'] = $app->share(
            function($app) {
                return new ProcessorService($app['api_consumer.fetcher'], $app['api_consumer.link_processor'], $app['links.model'], $app['dispatcher.service'], $app['users.rate.model'], $app['api_consumer.link_processor.link_resolver']);
            }
        );

        $app['api_consumer.processor_factory'] = $app->share(
            function ($app) {
                $processorFactory = new ProcessorFactory($app['api_consumer.resource_owner_factory'], $app['api_consumer.link_processor.processor.scrapper'], $app['api_consumer.config']['processor']);

                return $processorFactory;
            }
        );

        $app['api_consumer.fetcher'] = $app->share(
            function ($app) {

                $fetcher = new FetcherService(
                    $app['api_consumer.fetcher_factory'],
                    $app['dispatcher'],
                    $app['api_consumer.config']['fetcher']
                );

                if ($fetcher instanceof LoggerAwareInterface) {
                    $fetcher->setLogger($app['monolog']);
                }

                return $fetcher;
            }
        );

        $app['tweet_manager'] = $app->share(
            function ($app) {
                $tweetManager = new TweetManager($app['guzzle.client']);

                return $tweetManager;
            }
        );

        $app['get_old_tweets'] = $app->share(
            function ($app) {
                $getoldtweets = new GetOldTweets(new TwitterUrlParser(), $app['tweet_manager']);

                return $getoldtweets;
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
