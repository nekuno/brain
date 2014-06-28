<?php

use Provider\GuzzleServiceProvider;
use Provider\Neo4jPHPServiceProvider;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'    => 'pdo_mysql',
        'host'      => '127.0.0.1',
        'dbname'    => 'qnoow',
        'user'      => 'qnoowdev',
        'password'  => 'qnoow2014',
        'charset'   => 'utf8',
    ),
));
$app->register(new Neo4jPHPServiceProvider());
$app->register(new GuzzleServiceProvider());

// Sample yml config file ************************************REMOVE THIS SHIT!****************>>
$app->register(new \Igorw\Silex\ConfigServiceProvider(__DIR__ . '/../config/sample_config.yml'));

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
