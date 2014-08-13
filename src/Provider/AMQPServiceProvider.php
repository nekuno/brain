<?php


namespace Provider;


use PhpAmqpLib\Connection\AMQPConnection;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AMQPServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {

        $app['amqp'] = $app->share(
            function () use ($app) {

                return new AMQPConnection(
                    $app['amqp.options']['host'],
                    $app['amqp.options']['port'],
                    $app['amqp.options']['user'],
                    $app['amqp.options']['pass']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }

}
