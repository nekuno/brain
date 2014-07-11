<?php

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Provider\GuzzleServiceProvider;
use Provider\Neo4jPHPServiceProvider;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;

$app = new Application();

$app['env'] = getenv('APP_ENV') ?: 'prod';

$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/params.yml"));
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/params_{$app['env']}.yml"));

$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new DoctrineServiceProvider(), array(
    'dbs.options' => array(
        'mysql_local' => array(
            'driver'   => $app['dbs.mysql_local.driver'],
            'host'     => $app['dbs.mysql_local.host'],
            'dbname'   => $app['dbs.mysql_local.dbname'],
            'user'     => $app['dbs.mysql_local.user'],
            'password' => $app['dbs.mysql_local.pass'],
            'charset'  => 'utf8',
        ),

    ),
));

$app->register(new DoctrineOrmServiceProvider, array(
        "orm.proxies_dir" => '/path/to/proxies',
        "orm.em.options" => array(
            "mappings" => array(
                array(
                    "type" => "annotation",
                    "namespace" => 'Entities',
                    "resources_namespace" => 'Entities',
                ),
            ),
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
