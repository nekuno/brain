<?php


namespace Provider;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Silex\Application;
use Silex\ServiceProviderInterface;


class AMQPServiceProvider implements ServiceProviderInterface
{

    /**
     * @param Application $app
     */
    public function register(Application $app)
    {

        $app['amqp'] = $app->share(
            function ($app) {

                return new AMQPStreamConnection(
                    $app['amqp.options']['host'],
                    $app['amqp.options']['port'],
                    $app['amqp.options']['user'],
                    $app['amqp.options']['pass']
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }

}
