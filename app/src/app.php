<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
}));

// Initialize neo4j
$app['neo4j.client'] = $app->share(function($app) {
        $client = new Everyman\Neo4j\Client($app['neo4j.host'], $app['neo4j.port']);
        $client->getTransport()->setAuth($app['neo4j.user'], $app['neo4j.pass']);

        return $client;
    }
);

return $app;
