<?php

namespace Worker;

use ApiConsumer\Fetcher\FetcherService;
use ApiConsumer\Fetcher\ProcessorService;
use Event\ProcessLinksEvent;
use Model\Neo4j\Neo4jException;
use Psr\Log\LoggerInterface;
use Service\AMQPManager;
use Service\EventDispatcherHelper;

class LinkProcessorWorker extends LoggerAwareWorker implements RabbitMQConsumerInterface
{
    protected $queue = AMQPManager::FETCHING;

    /**
     * @var FetcherService
     */
    protected $fetcherService;

    /**
     * @var ProcessorService
     */
    protected $processorService;

    public function __construct(LoggerInterface $logger, EventDispatcherHelper $dispatcherHelper, FetcherService $fetcherService, ProcessorService $processorService)
    {
        parent::__construct($logger, $dispatcherHelper);
        $this->fetcherService = $fetcherService;
        $this->processorService = $processorService;
    }

    public function setLogger(LoggerInterface $logger)
    {
        parent::setLogger($logger);
        $this->fetcherService->setLogger($logger);
        $this->processorService->setLogger($logger);
    }

    /**
     * { @inheritdoc }
     */
    public function callback(array $data, $trigger)
    {
        $resourceOwner = $data['resourceOwner'];
        $userId = $data['userId'];
        $exclude = array_key_exists('exclude', $data) ? $data['exclude'] : array();
        $public = isset($data['public']) ? $data['public'] : false;

        try {
            $links = $public ? $this->fetcherService->fetchAsClient($userId, $resourceOwner, $exclude) : $this->fetcherService->fetchUser($userId, $resourceOwner, $exclude);

            $this->dispatcherHelper->dispatch(\AppEvents::PROCESS_START, new ProcessLinksEvent($userId, $resourceOwner, $links));
            $this->processorService->process($links, $userId);
            $this->dispatcherHelper->dispatch(\AppEvents::PROCESS_FINISH, new ProcessLinksEvent($userId, $resourceOwner, $links));

        } catch (\Exception $e) {
            $this->logger->error(sprintf('Worker: Error fetching for user %d with message %s on file %s, line %d', $userId, $e->getMessage(), $e->getFile(), $e->getLine()));
            if ($e instanceof Neo4jException) {
                $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
            }
            $this->dispatchError($e, 'Fetching');
        }
    }
}
