<?php

namespace Console\Command;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Fetcher\FetcherService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Worker\FetchLinkWorker;

class WorkerRabbitMQConsumeCommand extends ApplicationAwareCommand
{



    protected function configure()
    {

        $this->setName('worker:rabbitmq:consume')
            ->setDescription("Start RabbitMQ consumer by name")
            ->setDefinition(
                array(
                    new InputArgument('consumer', InputArgument::OPTIONAL, 'Consumer to start up', 'fetching')
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        /** @var UserProviderInterface $userProvider */
        $userProvider = $this->app['api_consumer.user_provider'];
        /** @var FetcherService $fetcher */
        $fetcher = $this->app['api_consumer.fetcher'];

        /** @var AMQPConnection $connection */
        $connection = $this->app['amqp'];

        $consumer = $input->getArgument('consumer');
        switch($consumer){
            case 'fetching':
                $output->writeln(sprintf('Starting fetching consumer'));
                /** @var AMQPChannel $channel */
                $channel = $connection->channel();
                $worker = new FetchLinkWorker($channel, $fetcher, $userProvider);
                $worker->consume();
                $channel->close();
                break;
        }

        $connection->close();
    }
}
