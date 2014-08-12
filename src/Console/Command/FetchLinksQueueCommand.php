<?php

namespace Console\Command;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Fetcher\FetcherService;
use PhpAmqpLib\Connection\AMQPConnection;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchLinksQueueCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('fetch:links:queue')
             ->setDescription("Process fetch-links queue")
             ->setDefinition(
             array()
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var AMQPConnection $amqp */
        $amqp = $this->app['amqp'];
        $channel = $amqp->channel();

        $channel->queue_declare('fetch-links', false, true, false, false);

        $channel->basic_consume('fetch-links', '', false, true, false, false, array($this, 'callback'));

        while(count($channel->callbacks)) {
            $channel->wait();
        }

        $output->writeln('Success!');
    }

    public function callback($msg){
        $message = unserialize($msg->body);
        $resourceOwner = $message['resourceOwner'];
        $userId = $message['userId'];

        $userProvider = new DBUserProvider($this->app['dbs']['mysql_social']);
        $user = $userProvider->getUsersByResource($this->app['api_consumer.config']['fetcher'][$resourceOwner]['resourceOwner'], $userId);

        /** @var FetcherService $fetcher */
        $fetcher = $this->app['api_consumer.fetcher'];

        $fetcher->fetch($user['id'], $resourceOwner);
    }
}
