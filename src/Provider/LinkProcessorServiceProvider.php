<?php


namespace Provider;

use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\LinkResolver;
use ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser;
use ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;
use Goutte\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class LinkProcessorServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {

        $app['api_consumer.link_processor.goutte'] = $app->share(
            function () {
                $client = new Client();
                $client->setMaxRedirects(10);

                return $client;
            }
        );

        $app['api_consumer.link_processor.processor.scrapper'] = $app->share(
            function ($app) {
                $basicMetadataParser = new BasicMetadataParser();
                $fbMetadataParser = new FacebookMetadataParser();

                return new ScraperProcessor($app['api_consumer.link_processor.goutte'], $basicMetadataParser, $fbMetadataParser);
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

        $app['api_consumer.link_processor.link_resolver'] = $app->share(
            function ($app) {

                return new LinkResolver($app['api_consumer.link_processor.goutte']);
            }
        );

        $app['api_consumer.link_processor.url_parser.parser'] = $app->share(
            function ($app) {

                return new UrlParser();
            }
        );

        $app['api_consumer.link_processor'] = $app->share(
            function ($app) {
                return new LinkProcessor(
                    $app['api_consumer.link_processor.link_resolver'],
                    $app['api_consumer.link_processor.link_analyzer'],
                    $app['links.model'],
                    $app['api_consumer.link_processor.processor.scrapper'],
                    $app['api_consumer.link_processor.processor.youtube'],
                    $app['api_consumer.link_processor.processor.spotify'],
                    $app['api_consumer.link_processor.url_parser.parser']
                );
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
