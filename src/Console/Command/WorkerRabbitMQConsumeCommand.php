<?php

namespace Console\Command;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\EventListener\FetchLinksSubscriber;
use ApiConsumer\Fetcher\FetcherService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
                    new InputArgument('consumer', InputArgument::OPTIONAL, 'Consumer to start up', 'fetching'),
                    new InputOption('debug', null, InputOption::VALUE_NONE, 'Debug the process to the console'),
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $consumer = $input->getArgument('consumer');

        if (!in_array($consumer, $this->validConsumers)) {
            throw new \Exception('Invalid consumer name');
        }

        /* @var $logger LoggerInterface */
        $logger = $this->app['monolog'];
        /* @var $userProvider UserProviderInterface */
        $userProvider = $this->app['api_consumer.user_provider'];
        /* @var $fetcher FetcherService */
        $fetcher = $this->app['api_consumer.fetcher'];

        if ($input->getOption('debug')) {

            $verbosityLevelMap = array(
                LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            );
            $logger = new ConsoleLogger($output, $verbosityLevelMap);

            $fetchLinksSubscriber = new FetchLinksSubscriber($output);
            $dispatcher = $this->app['dispatcher'];
            /* @var $dispatcher EventDispatcher */
            $dispatcher->addSubscriber($fetchLinksSubscriber);
        }

        $fetcher->setLogger($logger);

        /* @var  $connection AMQPStreamConnection */
        $connection = $this->app['amqp'];

        $output->writeln(sprintf('Starting %s consumer', $consumer));
        switch ($consumer) {
            case 'fetching':
                /* @var $channel AMQPChannel */
                $channel = $connection->channel();
                $worker = new LinkProcessorWorker($channel, $fetcher, $userProvider);
                $worker->setLogger($logger);
                $logger->info('Processing fetching queue');
                $worker->consume();
                $channel->close();
                break;
            case 'matching':
                /* @var $channel AMQPChannel */
                $channel = $connection->channel();
                $worker = new MatchingCalculatorWorker($channel, $this->app['users.model'], $this->app['users.matching.model'], $this->app['users.similarity.model']);
                $worker->setLogger($logger);
                $logger->info('Processing matching queue');
                $worker->consume();
                $channel->close();
                break;
        }

        $connection->close();
    }
}
