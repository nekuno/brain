<?php


namespace Provider;

use ApiConsumer\Factory\GoutteClientFactory;
use ApiConsumer\Images\ImageAnalyzer;
use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\LinkResolver;
use ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser;
use ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;
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
            function ($app) {
                $basicMetadataParser = new BasicMetadataParser();
                $fbMetadataParser = new FacebookMetadataParser();

                return new ScraperProcessor($app['api_consumer.link_processor.goutte_factory'], $basicMetadataParser, $fbMetadataParser);
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

                return new LinkResolver($app['api_consumer.link_processor.goutte_factory']);
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

        $app['api_consumer.link_processor.goutte_factory'] = $app->share(
            function () {
                return new GoutteClientFactory();
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
