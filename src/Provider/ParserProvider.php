<?php

namespace Provider;

use Goutte\Client;
use Model\Parser\LinkedinParser;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ParserProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {
        $app['parser_provider.client'] = $app->share(
            function () {
                $client = new Client();
                $client->setMaxRedirects(10);

                return $client;
            }
        );

        $app['parser.linkedin'] = $app->share(
            function (Application $app) {
                return new LinkedinParser($app['parser_provider.client']);
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
