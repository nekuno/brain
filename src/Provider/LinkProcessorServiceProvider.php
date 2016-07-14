<?php


namespace Provider;

use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\LinkResolver;
use ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser;
use ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser;
use ApiConsumer\LinkProcessor\Processor\FacebookProcessor;
use ApiConsumer\LinkProcessor\Processor\TwitterProcessor;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
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

                return new ScraperProcessor($app['api_consumer.link_processor.url_parser.parser'], $app['api_consumer.link_processor.goutte'], $basicMetadataParser, $fbMetadataParser);
            }
        );

	    foreach ($app['hwi_oauth']['resource_owners'] as $name => $options) {
		    if ($name == 'google') {
			    $name = 'youtube';
		    }
		    $app['api_consumer.link_processor.processor.' . $name] = $app->share(
			    function ($app) use ($name) {
				    if ($name == 'youtube') {
					    return new YoutubeProcessor($app['userAggregator.service'], $app['api_consumer.link_processor.processor.scrapper'], $app['api_consumer.resource_owner.google'], new YoutubeUrlParser(), $app['guzzle.client']);
				    }
				    elseif ($name == 'spotify') {
					    return new SpotifyProcessor($app['userAggregator.service'], $app['api_consumer.link_processor.processor.scrapper'], $app['api_consumer.resource_owner.spotify'], new SpotifyUrlParser(), $app['api_consumer.resource_owner.google'], new YoutubeUrlParser(), $app['guzzle.client']);
				    }
				    else {
					    $processorClass = 'ApiConsumer\\LinkProcessor\\Processor\\' . ucfirst($name) . 'Processor';
					    $urlParserClass = 'ApiConsumer\\LinkProcessor\\UrlParser\\' . ucfirst($name) . 'UrlParser';
					    return new $processorClass($app['userAggregator.service'], $app['api_consumer.link_processor.processor.scrapper'], $app['api_consumer.resource_owner.' . $name], new $urlParserClass(), $app['guzzle.client']);
				    }
			    }
		    );
	    }

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
                    $app['api_consumer.link_processor.processor.facebook'],
                    $app['api_consumer.link_processor.processor.twitter']
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
