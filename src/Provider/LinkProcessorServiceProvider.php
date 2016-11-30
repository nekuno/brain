<?php


namespace Provider;

use ApiConsumer\Images\ImageAnalyzer;
use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\LinkResolver;
use ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser;
use ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;
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

        $app['api_consumer.link_processor.link_analyzer'] = $app->share(
            function () {
                return new LinkAnalyzer();
            }
        );

        $app['api_consumer.link_processor.image_analyzer'] = $app->share(
            function ($app) {
                return new ImageAnalyzer($app['guzzle.client']);
            }
        );

        $app['api_consumer.link_processor.link_resolver'] = $app->share(
            function ($app) {

                return new LinkResolver($app['api_consumer.link_processor.goutte']);
            }
        );
        //TODO: Only dependency to ScrapperProcessor
        $app['api_consumer.link_processor.url_parser.parser'] = $app->share(
            function ($app) {

                return new UrlParser();
            }
        );

        $app['api_consumer.link_processor'] = $app->share(
            function ($app) {
                return new LinkProcessor(
                    $app['api_consumer.processor_factory'], $app['api_consumer.link_processor.image_analyzer']
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
