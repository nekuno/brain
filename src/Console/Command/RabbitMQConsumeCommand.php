<?php

namespace Console\Command;

use ApiConsumer\EventListener\CheckLinksSubscriber;
use ApiConsumer\EventListener\FetchLinksInstantSubscriber;
use ApiConsumer\EventListener\FetchLinksSubscriber;
use ApiConsumer\EventListener\ReprocessLinksSubscriber;
use Console\ApplicationAwareCommand;
use Model\Link\LinkManager;
use PhpAmqpLib\Channel\AMQPChannel;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Service\AMQPManager;
use Service\DeviceService;
use Service\InstantConnection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Worker\ChannelWorker;
use Worker\DatabaseReprocessorWorker;
use Worker\LinkProcessorWorker;
use Worker\LinksCheckWorker;
use Worker\LinksReprocessWorker;
use Worker\LoggerAwareWorker;
use Worker\MatchingCalculatorPeriodicWorker;
use Worker\MatchingCalculatorWorker;
use Worker\PredictionWorker;
use Worker\SocialNetworkDataProcessorWorker;


class RabbitMQConsumeCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'rabbitmq:consume';

    /**
     * @var AMQPManager
     */
    protected $AMQPManager;

    /**
     * @var LinkProcessorWorker
     */
    protected $linkProcessorWorker;

    /**
     * @var DatabaseReprocessorWorker
     */
    protected $databaseReprocessorWorker;

    /**
     * @var MatchingCalculatorWorker
     */
    protected $matchingCalculatorWorker;

    /**
     * @var MatchingCalculatorPeriodicWorker
     */
    protected $matchingCalculatorPeriodicWorker;

    /**
     * @var PredictionWorker
     */
    protected $predictionWorker;

    /**
     * @var SocialNetworkDataProcessorWorker
     */
    protected $socialNetworkDataProcessorWorker;

    /**
     * @var ChannelWorker
     */
    protected $channelWorker;

    /**
     * @var LinksCheckWorker
     */
    protected $linksCheckWorker;

    /**
     * @var LinksReprocessWorker
     */
    protected $linksReprocessWorker;

    /**
     * @var LinkManager
     */
    protected $linkManager;

    /**
     * @var DeviceService
     */
    protected $deviceService;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var InstantConnection
     */
    protected $instantConnection;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(
        LoggerInterface $logger,
        AMQPManager $AMQPManager,
        LinkProcessorWorker $linkProcessorWorker,
        DatabaseReprocessorWorker $databaseReprocessorWorker,
        MatchingCalculatorWorker $matchingCalculatorWorker,
        MatchingCalculatorPeriodicWorker $matchingCalculatorPeriodicWorker,
        PredictionWorker $predictionWorker,
        SocialNetworkDataProcessorWorker $socialNetworkDataProcessorWorker,
        ChannelWorker $channelWorker,
        LinksCheckWorker $linksCheckWorker,
        LinksReprocessWorker $linksReprocessWorker,
        LinkManager $linkManager,
        DeviceService $deviceService,
        EventDispatcherInterface $dispatcher,
        InstantConnection $instantConnection
    )
    {
        parent::__construct($logger);
        $this->AMQPManager = $AMQPManager;
        $this->linkProcessorWorker = $linkProcessorWorker;
        $this->databaseReprocessorWorker = $databaseReprocessorWorker;
        $this->matchingCalculatorWorker = $matchingCalculatorWorker;
        $this->matchingCalculatorPeriodicWorker = $matchingCalculatorPeriodicWorker;
        $this->predictionWorker = $predictionWorker;
        $this->socialNetworkDataProcessorWorker = $socialNetworkDataProcessorWorker;
        $this->channelWorker = $channelWorker;
        $this->linksCheckWorker = $linksCheckWorker;
        $this->linksReprocessWorker = $linksReprocessWorker;
        $this->linkManager = $linkManager;
        $this->deviceService = $deviceService;
        $this->dispatcher = $dispatcher;
        $this->instantConnection = $instantConnection;
    }

    protected function configure()
    {
        $this
            ->setDescription(sprintf('Starts a RabbitMQ consumer by name ("%s")', implode('", "', AMQPManager::getValidConsumers())))
            ->addArgument('consumer', InputArgument::OPTIONAL, 'Consumer to start up', 'fetching');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consumer = $input->getArgument('consumer');

        if (!in_array($consumer, AMQPManager::getValidConsumers())) {
            throw new \Exception(sprintf('Invalid "%s" consumer name, valid consumers "%s".', $consumer, implode('", "', AMQPManager::getValidConsumers())));
        }

        $this->setOutput($output);
        $this->setLogger($output);

        $output->writeln(sprintf('Starting %s consumer', $consumer));

        $channel = $this->AMQPManager->getChannel($consumer);

        switch ($consumer) {

            case AMQPManager::FETCHING :
                $worker = $this->buildFetching($channel);

                break;

            case AMQPManager::REFETCHING:
                $worker = $this->buildRefetching($channel);

                break;

            case AMQPManager::MATCHING:
                $worker = $this->buildMatching($channel);
                break;

            case AMQPManager::MATCHING_PERIODIC:
                $worker = $this->buildMatchingPeriodic($channel);
                break;

            case AMQPManager::PREDICTION:
                $worker = $this->buildPrediction($channel);
                break;

            case AMQPManager::SOCIAL_NETWORK:

                $worker = $this->buildSocialNetwork($channel);
                break;

            case AMQPManager::CHANNEL:

                $worker = $this->buildChannel($channel);
                break;

            case AMQPManager::LINKS_CHECK:

                $worker = $this->buildLinksCheck($channel);
                break;

            case AMQPManager::LINKS_REPROCESS:

                $worker = $this->buildLinksReprocess($channel);
                break;

            default:
                throw new \Exception('Invalid consumer name');
        }

        $worker->consume();
        $channel->close();
    }

    protected function setLogger(OutputInterface $output)
    {
        if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
            $this->logger = new ConsoleLogger($output, array(LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL));
        }
    }

    protected function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    protected function buildFetching(AMQPChannel $channel)
    {
        $subscribers = array(
            new FetchLinksInstantSubscriber($this->instantConnection, $this->deviceService),
            new FetchLinksSubscriber($this->output)
        );
        $this->addSubscribers($subscribers);

        $this->linkProcessorWorker->setChannel($channel);
        $this->linkProcessorWorker->setLogger($this->logger);
        $this->noticeStart($this->linkProcessorWorker);

        return $this->linkProcessorWorker;
    }

    protected function buildRefetching(AMQPChannel $channel)
    {
        $subscribers = array(
            new FetchLinksInstantSubscriber($this->instantConnection, $this->deviceService),
            new FetchLinksSubscriber($this->output)
        );
        $this->addSubscribers($subscribers);

        $this->databaseReprocessorWorker->setChannel($channel);
        $this->databaseReprocessorWorker->setLogger($this->logger);
        $this->noticeStart($this->databaseReprocessorWorker);

        return $this->databaseReprocessorWorker;
    }

    /**
     * @param AMQPChannel $channel
     * @return MatchingCalculatorWorker
     * @internal param OutputInterface $output
     */
    protected function buildMatching(AMQPChannel $channel)
    {
        $this->matchingCalculatorWorker->setChannel($channel);
        $this->matchingCalculatorWorker->setLogger($this->logger);
        $this->noticeStart($this->matchingCalculatorWorker);

        return $this->matchingCalculatorWorker;
    }


    /**
     * @param AMQPChannel $channel
     * @return MatchingCalculatorPeriodicWorker
     * @internal param OutputInterface $output
     */
    protected function buildMatchingPeriodic(AMQPChannel $channel)
    {
        $this->matchingCalculatorPeriodicWorker->setChannel($channel);
        $this->matchingCalculatorPeriodicWorker->setLogger($this->logger);
        $this->noticeStart($this->matchingCalculatorPeriodicWorker);

        return $this->matchingCalculatorPeriodicWorker;
    }

    /**
     * @param $channel
     * @return PredictionWorker
     */
    protected function buildPrediction(AMQPChannel $channel)
    {
        $this->predictionWorker->setChannel($channel);
        $this->predictionWorker->setLogger($this->logger);
        $this->noticeStart($this->predictionWorker);

        return $this->predictionWorker;
    }

    /**
     * @param $channel
     * @return SocialNetworkDataProcessorWorker
     */
    protected function buildSocialNetwork(AMQPChannel $channel)
    {
        $this->socialNetworkDataProcessorWorker->setChannel($channel);
        $this->socialNetworkDataProcessorWorker->setLogger($this->logger);
        $this->noticeStart($this->socialNetworkDataProcessorWorker);

        return $this->socialNetworkDataProcessorWorker;
    }

    /**
     * @param $channel
     * @return ChannelWorker
     */
    protected function buildChannel(AMQPChannel $channel)
    {
        $subscribers = array(
            new FetchLinksSubscriber($this->output)
        );
        $this->addSubscribers($subscribers);

        $this->channelWorker->setChannel($channel);
        $this->channelWorker->setLogger($this->logger);
        $this->noticeStart($this->channelWorker);

        return $this->channelWorker;
    }

    /**
     * @param $channel
     * @return LinksCheckWorker
     */
    protected function buildLinksCheck(AMQPChannel $channel)
    {
        $subscribers = array(
            new CheckLinksSubscriber($this->output, $this->linkManager)
        );
        $this->addSubscribers($subscribers);

        $this->linksCheckWorker->setChannel($channel);
        $this->linksCheckWorker->setLogger($this->logger);
        $this->noticeStart($this->linksCheckWorker);

        return $this->linksCheckWorker;
    }

    /**
     * @param $channel
     * @return LinksReprocessWorker
     */
    protected function buildLinksReprocess(AMQPChannel $channel)
    {
        $subscribers = array(
            new ReprocessLinksSubscriber($this->output, $this->linkManager)
        );
        $this->addSubscribers($subscribers);

        $this->linksReprocessWorker->setChannel($channel);
        $this->linksReprocessWorker->setLogger($this->logger);
        $this->noticeStart($this->linksReprocessWorker);

        return $this->linksReprocessWorker;
    }

    protected function noticeStart(LoggerAwareWorker $worker)
    {
        $message = 'Processing %s queue';
        $this->logger->notice(sprintf($message, $worker->getQueue()));
    }

    /**
     * @param EventSubscriberInterface[] $subscribers
     */
    protected function addSubscribers(array $subscribers)
    {
        foreach ($subscribers as $subscribe)
        {
            $this->dispatcher->addSubscriber($subscribe);
        }
    }
}
