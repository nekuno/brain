<?php

namespace Provider;

use Controller\ControllerResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ServiceControllerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['resolver'] = $app->share(
            function () use ($app) {
                return new ControllerResolver($app, $app['logger']);
            }
        );
    }

    public function boot(Application $app)
    {
        // noop
    }
}
