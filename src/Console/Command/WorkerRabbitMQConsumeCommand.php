<?php

namespace Console\Command;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Fetcher\FetcherService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Worker\LinkProcessorWorker;
use Worker\MatchingCalculatorWorker;

/**
 * Class WorkerRabbitMQConsumeCommand
 *
 * @package Console\Command
 */
class WorkerRabbitMQConsumeCommand extends ApplicationAwareCommand
{

    protected $validConsumers = array(
        'fetching',
        'matching',
    );

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

        $consumer = $input->getArgument('consumer');

        if (!in_array($consumer, $this->validConsumers)) {
            throw new \Exception('Invalid consumer name');
        }

        /** @var LoggerInterface $logger */
        $logger = $this->app['monolog'];

        /** @var UserProviderInterface $userProvider */
        $userProvider = $this->app['api_consumer.user_provider'];
        /** @var FetcherService $fetcher */
        $fetcher = $this->app['api_consumer.fetcher'];

        /** @var AMQPStreamConnection $connection */
        $connection = $this->app['amqp'];

        $output->writeln(sprintf('Starting %s consumer', $consumer));
        switch ($consumer) {
            case 'fetching':
                /** @var AMQPChannel $channel */
                $channel = $connection->channel();
                $worker  = new LinkProcessorWorker($channel, $fetcher, $userProvider);
                $worker->setLogger($logger);
                $logger->info('Processing fetching queue');
                $worker->consume();
                $channel->close();
                break;
            case 'matching':
                /** @var AMQPChannel $channel */
                $channel = $connection->channel();
                $worker  = new MatchingCalculatorWorker($channel, $this->app['users.model'], $this->app['users.matching.model'], $this->app['users.similarity.model']);
                $worker->setLogger($logger);
                $logger->info('Processing matching queue');
                $worker->consume();
                $channel->close();
                break;
        }

        $connection->close();
    }
}
