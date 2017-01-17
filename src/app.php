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
use Provider\ParserProvider;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Provider\SwiftmailerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Sorien\Provider\PimpleDumpProvider;

$app = new Application();

$app['env'] = getenv('APP_ENV') ?: 'prod';
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/params.yml"));
$replacements = array_merge($app['params'], array('app_root_dir' => __DIR__));
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/config.yml", $replacements));
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/config_{$app['env']}.yml", $replacements));
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/fields.yml", array(), null, 'fields'));
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/socialFields.yml", array(), null, 'socialFields'));
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/consistency.yml", array(), null, 'consistency'));
$app->register(new MonologServiceProvider(), array('monolog.name' => 'brain', 'monolog.level' => $app['debug'] ? \Monolog\Logger::DEBUG : \Monolog\Logger::ERROR, 'monolog.logfile' => __DIR__ . "/../var/logs/silex_{$app['env']}.log"));
$app['images_web_dir'] = __DIR__ . $app['images_relative_dir'];
$app->register(new DoctrineServiceProvider());
$app->register(new DoctrineOrmServiceProvider());
$app->register(new Neo4jPHPServiceProvider());
$app->register(new UrlGeneratorServiceProvider());
$app->register(new GuzzleServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new \Provider\ServiceControllerServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new ApiConsumerServiceProvider());
$app->register(new LinkProcessorServiceProvider());
$app->register(new ParserProvider());
$app->register(new LookUpServiceProvider());
$app->register(new PaginatorServiceProvider());
$app->register(new SwiftmailerServiceProvider());
$app->register(new AMQPServiceProvider());
$app->register(new TranslationServiceProvider(), array('locale_fallbacks' => array('en', 'es')));
$app->register(new ServicesServiceProvider());
$app->register(new ModelsServiceProvider());
$app->register(new \Provider\OAuthServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider());
$app['security.jwt'] = array(
    'secret_key' => $app['secret'],
    'life_time' => $app['life_time'],
    'options' => array(
        'username_claim' => 'sub', // default name, option specifying claim containing username
        'header_name' => 'Authorization', // default null, option for usage normal oauth2 header
        'token_prefix' => 'Bearer',
    )
);
$app->register(new Silex\Provider\SecurityJWTServiceProvider());
$app->register(new SubscribersServiceProvider());
$app->register(new TwigServiceProvider(), array('twig.path' => __DIR__ . '/views'));
$app->register(new PimpleDumpProvider());

return $app;
