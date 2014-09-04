<?php

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Provider\AMQPServiceProvider;
use Provider\GuzzleServiceProvider;
use Provider\LinkProcessorServiceProvider;
use Provider\Neo4jPHPServiceProvider;
use Provider\PaginatorServiceProvider;
use Provider\ApiConsumerServiceProvider;
use ApiConsumer\EventListener\OAuthTokenSubscriber;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\MonologServiceProvider;

$app = new Application();

$app['env'] = getenv('APP_ENV') ?: 'prod';

$app->register(new DoctrineServiceProvider());
$app->register(new DoctrineOrmServiceProvider());
$app->register(new Neo4jPHPServiceProvider());

$app->register(new MonologServiceProvider());
$app->register(new UrlGeneratorServiceProvider());
$app->register(new GuzzleServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());

$app->register(new ApiConsumerServiceProvider());
$app->register(new LinkProcessorServiceProvider());
$app->register(new PaginatorServiceProvider());

$app->register(new SwiftmailerServiceProvider());
$app->register(new AMQPServiceProvider());

//Config
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/params.yml"));

$replacements = array_merge($app['params'], array('app_root_dir' => __DIR__));
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/config.yml", $replacements));
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/config_{$app['env']}.yml", $replacements));

// Listeners

/** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
$dispatcher = $app['dispatcher'];
$tokenRefreshedSubscriber = new OAuthTokenSubscriber($app['api_consumer.user_provider'], $app['mailer'], $app['monolog'], $app['amqp']);
$dispatcher->addSubscriber($tokenRefreshedSubscriber);

$linkProcessSubscriber = new \ApiConsumer\EventListener\LinkProcessSubscriber($app['amqp']);
$dispatcher->addSubscriber($linkProcessSubscriber);

return $app;
