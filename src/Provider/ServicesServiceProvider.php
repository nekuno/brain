<?php

namespace Provider;

use GuzzleHttp\Client;
use Service\AffinityRecalculations;
use Service\ChatMessageNotifications;
use Service\MigrateSocialInvitations;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ServicesServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {
        $app['chatMessageNotifications.service'] = $app->share(
            function (Application $app) {
                return new ChatMessageNotifications($app['emailNotification.service'], $app['orm.ems']['mysql_brain'], $app['dbs']['mysql_social'], $app['translator'], $app['users.model'], $app['users.profile.model']);
            }
        );

        $app['affinityRecalculations.service'] = $app->share(
            function (Application $app) {
                return new AffinityRecalculations($app['emailNotification.service'], $app['translator'], $app['neo4j.graph_manager'], $app['links.model'], $app['users.model'], $app['users.affinity.model']);
            }
        );

        $app['migrateSocialInvitations.service'] = $app->share(
            function (Application $app) {
                return new MigrateSocialInvitations($app['neo4j.graph_manager'], $app['dbs']['mysql_social']);
            }
        );

        $app['instant.client'] = $app->share(
            function (Application $app) {
                return new Client(array('base_url' => $app['instant.host']));
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
