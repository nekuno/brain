<?php

use Provider\Neo4jPHPServiceProvider;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new Neo4jPHPServiceProvider());

$app['twig'] = $app->share(
    $app->extend(
        'twig',
        function ($twig, $app) {
            // add custom globals, filters, tags, ...

            return $twig;
        }
    )
);

return $app;
