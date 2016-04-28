<?php

namespace Provider;

use Service\AffinityRecalculations;
use Service\AMQPManager;
use Service\AuthService;
use Service\ChatMessageNotifications;
use Service\EmailNotifications;
use Service\MigrateSocialInvitations;
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
                return new AuthService($app['users.manager'], $app['security.password_encoder'], $app['security.jwt.encoder']);
            }
        );

        $app['chatMessageNotifications.service'] = $app->share(
            function (Application $app) {
                return new ChatMessageNotifications($app['emailNotification.service'], $app['orm.ems']['mysql_brain'], $app['dbs']['mysql_social'], $app['translator'], $app['users.manager'], $app['users.profile.model']);
            }
        );

        $app['affinityRecalculations.service'] = $app->share(
            function (Application $app) {
                return new AffinityRecalculations($app['emailNotification.service'], $app['translator'], $app['neo4j.graph_manager'], $app['links.model'], $app['users.manager'], $app['users.affinity.model']);
            }
        );

        $app['socialNetwork.service'] = $app->share(
            function (Application $app) {
                return new SocialNetwork($app['users.socialNetwork.linkedin.model'], $app['users.lookup.model'], $app['api_consumer.fetcher_factory']);
            }
        );

        $app['migrateSocialInvitations.service'] = $app->share(
            function (Application $app) {
                return new MigrateSocialInvitations($app['neo4j.graph_manager'], $app['dbs']['mysql_social']);
            }
        );

        $app['emailNotification.service'] = $app->share(
            function (Application $app) {
                return new EmailNotifications($app['mailer'], $app['orm.ems']['mysql_brain'], $app['twig']);
            }
        );

        $app['recommendator.service'] = $app->share(
            function (Application $app) {
                return new Recommendator(
                    $app['paginator'], $app['paginator.content'], $app['users.groups.model'],
                    $app['users.manager'], $app['users.recommendation.users.model'],
                    $app['users.socialRecommendation.users.model'], $app['users.recommendation.content.model']
                );
            }
        );

        $app['userAggregator.service'] = $app->share(
            function (Application $app) {
                return new UserAggregator(
                    $app['users.manager'], $app['users.ghostuser.manager'], $app['users.socialprofile.manager'],
                    $app['api_consumer.resource_owner_factory'], $app['users.lookup.model'], $app['amqpManager.service']
                );
            }
        );

        $app['validator.service'] = $app->share(
            function (Application $app) {
                return new Validator($app['users.manager'], $app['users.profileFilter.model'], $app['users.userFilter.model'], $app['users.contentFilter.model'], $app['fields']);
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

    }

    /**
     * { @inheritdoc }
     */
    public function boot(Application $app)
    {

    }

}
