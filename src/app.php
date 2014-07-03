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

$app['env'] = getenv('APP_ENV') ? : 'prod';

$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/params.yml"));
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/params_{$app['env']}.yml"));

$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'host'     => '127.0.0.1',
        'dbname'   => isset($app['db.dbname']) ? $app['db.dbname'] : 'qnoow',
        'user'     => isset($app['db.user']) ? $app['db.user'] : 'qnoowdev',
        'password' => isset($app['db.password']) ? $app['db.password'] : 'qnoow2014',
        'charset'  => 'utf8',
    ),
));
$app->register(new Neo4jPHPServiceProvider());
$app->register(new GuzzleServiceProvider());

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
