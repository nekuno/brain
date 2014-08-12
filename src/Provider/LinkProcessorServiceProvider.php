<?php


namespace Provider;

use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\Scrapper\Scraper;
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

        $app['link_processor'] = function () use ($app) {

            $goutte = new Client();
            $scraper = new Scraper($goutte);

            return new LinkProcessor($scraper, $app['api_consumer.get_resource_owner_by_name']);
        };

    }

    /**
     * { @inheritdoc }
     */
    public function boot(Application $app)
    {
        // TODO: Implement boot() method.
    }
}
