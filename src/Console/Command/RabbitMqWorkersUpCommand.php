<?php

namespace Console\Command;

use Console\Worker\FetchLinksWorker;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

        $amqp = $this->app['amqp'];
        $fetcher = $this->app['api_consumer.fetcher'];
        $userProvider = $this->app['api_consumer.user_provider'];

        $fetchLinksWorker = new FetchLinksWorker($amqp, $fetcher, $userProvider);
        $fetchLinksWorker->process();

        $amqp->close();

    }

}
