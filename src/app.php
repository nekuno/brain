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
$app->register(
    new DoctrineServiceProvider(),
    array(
        'dbs.options' => array(
            'mysql_social' => array(
                'driver'   => $app['dbs.mysql_social.driver'],
                'host'     => $app['dbs.mysql_social.host'],
                'dbname'   => $app['dbs.mysql_social.dbname'],
                'user'     => $app['dbs.mysql_social.user'],
                'password' => $app['dbs.mysql_social.pass'],
                'charset'  => 'utf8',
            ),
            'mysql_brain' => array(
                'driver'   => $app['dbs.mysql_brain.driver'],
                'host'     => $app['dbs.mysql_brain.host'],
                'dbname'   => $app['dbs.mysql_brain.dbname'],
                'user'     => $app['dbs.mysql_brain.user'],
                'password' => $app['dbs.mysql_brain.pass'],
                'charset'  => 'utf8',
            ),
        ),
    )
);
$app->register(
    new DoctrineOrmServiceProvider,
    array(
        "orm.proxies_dir"       => __DIR__. '/../cache/DoctrineProxy',
        "orm.ems.default"       => 'mysql_brain',
        "orm.ems.options"       => array(
            'mysql_brain' => array(
                "connection"                   => 'mysql_brain',
                "mappings"                     => array(
                    array(
                        "type"      => "annotation",
                        "namespace" => 'Model\Entity',
                        "path"      => __DIR__ . "/Model/Entity",
                    ),
                ),
                "use_simple_annotation_reader" => false
            ),

        ),
    )
);

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
