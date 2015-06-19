<?php

use ApiConsumer\EventListener\OAuthTokenSubscriber;
use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use EventListener\UserAnswerSubscriber;
use EventListener\UserDataStatusSubscriber;
use EventListener\InvitationSubscriber;
use Provider\AMQPServiceProvider;
use Provider\ApiConsumerServiceProvider;
use Provider\GuzzleServiceProvider;
use Provider\LinkProcessorServiceProvider;
use Provider\Neo4jPHPServiceProvider;
use Provider\PaginatorServiceProvider;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Symfony\Component\Translation\Loader\YamlFileLoader;

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
$app->register(new PaginatorServiceProvider());

$app->register(new SwiftmailerServiceProvider());
$app->register(new AMQPServiceProvider());

$app->register(new TwigServiceProvider(), array('twig.path' => __DIR__.'/views'));
$app->register(new TranslationServiceProvider(), array('locale_fallbacks' => array('en', 'es')));

//Config
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/params.yml"));

$replacements = array_merge($app['params'], array('app_root_dir' => __DIR__));
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/config.yml", $replacements));
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/config_{$app['env']}.yml", $replacements));

$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__ . "/../config/fields.yml", array(), null, 'fields'));

/**
 * Event system configuration. Initialize the listeners and subscribers below.
 */

/** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
$dispatcher = $app['dispatcher'];

$tokenRefreshedSubscriber = new OAuthTokenSubscriber(
    $app['api_consumer.user_provider'],
    $app['mailer'],
    $app['monolog'],
    $app['amqp']
);
$dispatcher->addSubscriber($tokenRefreshedSubscriber);

$statusSubscriber = new UserDataStatusSubscriber($app['orm.ems']['mysql_brain'], $app['amqp']);
$dispatcher->addSubscriber($statusSubscriber);

$answerSubscriber = new UserAnswerSubscriber($app['amqp']);
$dispatcher->addSubscriber($answerSubscriber);

$invitationSubscriber = new InvitationSubscriber($app['neo4j.graph_manager']);
$dispatcher->addSubscriber($invitationSubscriber);

/**
 * Services configuration.
 */
$app['emailNotification.service'] = function (Silex\Application $app) {
    return new \Service\EmailNotifications($app['mailer'], $app['orm.ems']['mysql_brain'], $app['twig']);
};

$app['translator'] = $app->share($app->extend('translator', function($translator) {
    $translator->addLoader('yaml', new YamlFileLoader());

    $translator->addResource('yaml', __DIR__.'/locales/en.yml', 'en');
    $translator->addResource('yaml', __DIR__.'/locales/es.yml', 'es');

    return $translator;
}));

$app['tokenGenerator.service'] = function () {
    return new \Service\TokenGenerator();
};


return $app;
