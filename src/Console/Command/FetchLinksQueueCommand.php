<?php

namespace Console\Command;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Fetcher\FetcherService;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
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

        $queueName = 'fetch';
        $exchange = 'social';

        /** @var AMQPConnection $amqp */
        $amqp = $this->app['amqp'];
        $channel = $amqp->channel();

        $channel->queue_declare($queueName, false, true, false, false);

        $channel->queue_bind($queueName, $exchange);

        $output->writeln('[*] Waiting for messages. To exit press CTRL+C');

        $channel->basic_qos(null, 1, null);

        $channel->basic_consume($queueName, '', false, false, false, false, array($this, 'processQueuedMessage'));

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $amqp->close();

    }

    public function processQueuedMessage(AMQPMessage $message)
    {
        $messageBody = unserialize($message->body);
        $resourceOwner = $messageBody['resourceOwner'];
        $userId = $messageBody['userId'];

        $userProvider = new DBUserProvider($this->app['dbs']['mysql_social']);
        $user = $userProvider->getUsersByResource(
            $this->app['api_consumer.config']['fetcher'][$resourceOwner]['resourceOwner'],
            $userId
        );

        /** @var FetcherService $fetcher */
        $fetcher = $this->app['api_consumer.fetcher'];

        $fetcher->fetch($user['id'], $resourceOwner);

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }
}
