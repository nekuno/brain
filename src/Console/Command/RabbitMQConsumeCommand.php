<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use PhpAmqpLib\Channel\AMQPChannel;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Service\AMQPManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
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
        LinksReprocessWorker $linksReprocessWorker
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

    protected function buildFetching(AMQPChannel $channel)
    {
        $this->linkProcessorWorker->setChannel($channel);
        $this->linkProcessorWorker->setLogger($this->logger);
        $this->noticeStart($this->linkProcessorWorker);

        return $this->linkProcessorWorker;
    }

    protected function buildRefetching(AMQPChannel $channel)
    {
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
}
