<?php

namespace Console\Command;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Fetcher\FetcherService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Worker\FetchLinksWorker;

class RabbitMqWorkersUpCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('workers:rabbitmq:up')
            ->setDescription("Start RabbitMQ workers")
            ->setDefinition(
                array()
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var AMQPConnection $connection */
        $connection = $this->app['amqp'];

        /** @var UserProviderInterface $userProvider */
        $userProvider = $this->app['api_consumer.user_provider'];

        /** @var FetcherService $fetcher */
        $fetcher = $this->app['api_consumer.fetcher'];

        /** @var AMQPChannel $fetchChannel */
        $fetchChannel = $connection->channel();
        $fetchLinksWorker = new FetchLinksWorker($fetchChannel, $fetcher, $userProvider, $this->app['api_consumer.config']);
        $fetchLinksWorker->consume();
        $fetchChannel->close();

        $connection->close();

    }

}
