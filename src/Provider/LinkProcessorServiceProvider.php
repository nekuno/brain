<?php


namespace Provider;

use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;
use Silex\Application;
use Silex\ServiceProviderInterface;

class LinkProcessorServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {

        $app['api_consumer.link_processor.processor.scrapper'] = $app->share(
            function () {
                return new ScraperProcessor();
            }
        );

        $app['api_consumer.link_processor.processor.youtube'] = $app->share(
            function ($app) {
                return new YoutubeProcessor($app['api_consumer.resource_owner.google'], new YoutubeUrlParser());
            }
        );

        $app['api_consumer.link_processor.processor.spotify'] = $app->share(
            function ($app) {
                return new SpotifyProcessor($app['api_consumer.resource_owner.spotify'], new SpotifyUrlParser());
            }
        );

        $app['api_consumer.link_processor.link_analyzer'] = $app->share(
            function () {
                return new LinkAnalyzer();
            }
        );

        $app['api_consumer.link_processor'] = $app->share(
            function ($app) {
                return new LinkProcessor($app['api_consumer.link_processor.link_analyzer'], $app['api_consumer.link_processor.processor.scrapper'], $app['api_consumer.link_processor.processor.youtube'], $app['api_consumer.link_processor.processor.spotify']);
            }
        );

    }

    /**
     * { @inheritdoc }
     */
    public function boot(Application $app)
    {
        // TODO: Implement boot() method.
    }
}
