<?php

namespace Provider;

use GuzzleHttp\Client;
use Service\LookUp\LookUpFullContact;
use Service\LookUp\LookUpPeopleGraph;
use Silex\Application;
use Silex\ServiceProviderInterface;

class LookUpServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {

        $app['fullContact.client'] = $app->share(
            function (Application $app) {
                return new Client(array('base_url' => $app['fullContact.url']));
            }
        );

        $app['peopleGraph.client'] = $app->share(
            function (Application $app) {
                return new Client(array('base_url' => $app['peopleGraph.url']));
            }
        );

        $app['lookUp.fullContact.service'] = $app->share(
            function (Application $app) {
                return new LookUpFullContact($app['fullContact.client'], $app['fullContact.consumer_key'], $app['url_generator']);
            }
        );

        $app['lookUp.peopleGraph.service'] = $app->share(
            function (Application $app) {
                return new LookUpPeopleGraph($app['peopleGraph.client'], $app['peopleGraph.consumer_key'], $app['url_generator']);
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
