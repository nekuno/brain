<?php


namespace Provider;

use ApiConsumer\Scraper\LinkProcessor;
use ApiConsumer\Scraper\Scraper;
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

        $app['link_processor'] = function () {

            $goutte  = new Client();
            $scraper = new Scraper($goutte);

            return new LinkProcessor($scraper);
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
