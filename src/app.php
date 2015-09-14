<?php

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Igorw\Silex\ConfigServiceProvider;
use Provider\AMQPServiceProvider;
use Provider\ApiConsumerServiceProvider;
use Provider\GuzzleServiceProvider;
use Provider\LinkProcessorServiceProvider;
use Provider\LookUpServiceProvider;
use Provider\Neo4jPHPServiceProvider;
use Provider\ModelsServiceProvider;
use Provider\PaginatorServiceProvider;
use Provider\ServicesServiceProvider;
use Provider\SubscribersServiceProvider;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\TranslationServiceProvider;

$app = new Application();

$app['env'] = getenv('APP_ENV') ?: 'prod';

$app->register(new MonologServiceProvider(), array('monolog.name' => 'brain'));
$app->register(new DoctrineServiceProvider());
$app->register(new DoctrineOrmServiceProvider());
$app->register(new Neo4jPHPServiceProvider());
$app->register(new UrlGeneratorServiceProvider());
$app->register(new GuzzleServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new ApiConsumerServiceProvider());
$app->register(new LinkProcessorServiceProvider());
$app->register(new LookUpServiceProvider());
$app->register(new PaginatorServiceProvider());
$app->register(new SwiftmailerServiceProvider());
$app->register(new AMQPServiceProvider());
$app->register(new TwigServiceProvider(), array('twig.path' => __DIR__ . '/views'));
$app->register(new TranslationServiceProvider(), array('locale_fallbacks' => array('en', 'es')));
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/params.yml"));
$replacements = array_merge($app['params'], array('app_root_dir' => __DIR__));
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/config.yml", $replacements));
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/config_{$app['env']}.yml", $replacements));
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/fields.yml", array(), null, 'fields'));
$app->register(new SubscribersServiceProvider());
$app->register(new ServicesServiceProvider());
$app->register(new ModelsServiceProvider());

return $app;
