<?php

namespace Provider;

use ApiConsumer\EventListener\OAuthTokenSubscriber;
use EventListener\FilterClientIpSubscriber;
use EventListener\InvitationSubscriber;
use EventListener\UserAnswerSubscriber;
use EventListener\UserDataStatusSubscriber;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SubscribersServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {

    }

    /**
     * { @inheritdoc }
     */
    public function boot(Application $app)
    {

        /* @var $dispatcher EventDispatcher */
        $dispatcher = $app['dispatcher'];

        $dispatcher->addSubscriber(new FilterClientIpSubscriber($app['valid_ips']));
        $dispatcher->addSubscriber(new OAuthTokenSubscriber($app['api_consumer.user_provider'], $app['mailer'], $app['monolog'], $app['amqp']));
        $dispatcher->addSubscriber(new UserDataStatusSubscriber($app['orm.ems']['mysql_brain'], $app['amqp']));
        $dispatcher->addSubscriber(new UserAnswerSubscriber($app['amqp']));
        $dispatcher->addSubscriber(new InvitationSubscriber($app['neo4j.graph_manager']));
    }

}
