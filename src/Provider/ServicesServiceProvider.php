<?php

namespace Provider;

use Service\AffinityRecalculations;
use Service\AMQPManager;
use Service\AuthService;
use Service\ChatMessageNotifications;
use Service\Consistency\ConsistencyCheckerService;
use Service\EmailNotifications;
use Service\EventDispatcher;
use Service\ImageTransformations;
use Service\NotificationManager;
use Service\Recommendator;
use Service\SocialNetwork;
use Service\TokenGenerator;
use Service\UserAggregator;
use Service\Validator;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;

class ServicesServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {

        $app['auth.service'] = $app->share(
            function (Application $app) {
                return new AuthService($app['users.manager'], $app['security.password_encoder'], $app['security.jwt.encoder'], $app['oauth.service']);
            }
        );

        $app['chatMessageNotifications.service'] = $app->share(
            function (Application $app) {
                return new ChatMessageNotifications($app['emailNotification.service'], $app['orm.ems']['mysql_brain'], $app['dbs']['mysql_brain'], $app['translator'], $app['users.manager'], $app['users.profile.model']);
            }
        );

        $app['affinityRecalculations.service'] = $app->share(
            function (Application $app) {
                return new AffinityRecalculations($app['dispatcher.service'], $app['emailNotification.service'], $app['translator'], $app['neo4j.graph_manager'], $app['links.model'], $app['users.manager'], $app['users.affinity.model']);
            }
        );

        $app['socialNetwork.service'] = $app->share(
            function (Application $app) {
                return new SocialNetwork($app['users.socialNetwork.linkedin.model'], $app['users.lookup.model'], $app['api_consumer.fetcher_factory']);
            }
        );

        $app['emailNotification.service'] = $app->share(
            function (Application $app) {
                return new EmailNotifications($app['mailer'], $app['orm.ems']['mysql_brain'], $app['twig']);
            }
        );

        $app['imageTransformations.service'] = $app->share(
            function () {
                return new ImageTransformations();
            }
        );

        $app['recommendator.service'] = $app->share(
            function (Application $app) {
                return new Recommendator(
                    $app['paginator'], $app['paginator.content'], $app['users.groups.model'],
                    $app['users.manager'], $app['users.recommendation.users.model'],
                    $app['users.socialRecommendation.users.model'], $app['users.recommendation.content.model'],
                    $app['users.recommendation.popularusers.model'], $app['users.recommendation.popularcontent.model']
                );
            }
        );

        $app['userAggregator.service'] = $app->share(
            function (Application $app) {
                return new UserAggregator(
                    $app['users.manager'], $app['users.ghostuser.manager'], $app['users.socialprofile.manager'],
                    $app['api_consumer.resource_owner_factory'], $app['socialNetwork.service'], $app['users.lookup.model'], $app['amqpManager.service']
                );
            }
        );

        $app['validator.service'] = $app->share(
            function (Application $app) {
                return new Validator($app['neo4j.graph_manager'], $app['users.profileFilter.model'], $app['users.userFilter.model'], $app['users.contentFilter.model'], $app['fields']);
            }
        );

        $app['translator'] = $app->share(
            $app->extend(
                'translator',
                function (Translator $translator) {

                    $translator->addLoader('yaml', new YamlFileLoader());
                    $translator->addResource('yaml', __DIR__ . '/../locales/en.yml', 'en');
                    $translator->addResource('yaml', __DIR__ . '/../locales/es.yml', 'es');

                    return $translator;
                }
            )
        );

        $app['tokenGenerator.service'] = $app->share(
            function () {
                return new TokenGenerator();
            }
        );

        $app['amqpManager.service'] = $app->share(
            function (Application $app) {
                return new AMQPManager($app['amqp']);
            }
        );

        $app['notificationManager.service'] = $app->share(
            function (Application $app) {
                return new NotificationManager($app['neo4j.graph_manager']);
            }
        );

        $app['consistency.service'] = $app->share(
            function (Application $app) {
                return new ConsistencyCheckerService($app['neo4j.graph_manager'], $app['dispatcher'], $app['consistency']);
            }
        );

        $app['dispatcher.service'] = $app->share(
            function (Application $app) {
                return new EventDispatcher($app['dispatcher']);
            }
        );

    }

    /**
     * { @inheritdoc }
     */
    public function boot(Application $app)
    {

    }

}
