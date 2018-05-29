<?php

namespace Worker;

use ApiConsumer\Fetcher\ProcessorService;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Event\ReprocessEvent;
use Model\Link\Link;
use Model\Link\LinkManager;
use Psr\Log\LoggerInterface;
use Service\AMQPManager;
use Service\EventDispatcherHelper;

class LinksReprocessWorker extends LoggerAwareWorker implements RabbitMQConsumerInterface
{
    protected $queue = AMQPManager::LINKS_REPROCESS;

    /**
     * @var LinkManager
     */
    protected $linkModel;

    /**
     * @var ProcessorService
     */
    protected $processorService;

    public function __construct(LoggerInterface $logger, EventDispatcherHelper $dispatcherHelper, LinkManager $linkModel, ProcessorService $processorService)
    {
        parent::__construct($logger, $dispatcherHelper);
        $this->linkModel = $linkModel;
        $this->processorService = $processorService;
    }

    /**
     * { @inheritdoc }
     */
    public function callback(array $data, $trigger)
    {
        $link = Link::buildFromArray($data['link']);
        $url = $link->getUrl();
        $reprocessEvent = new ReprocessEvent($url);
        $this->dispatcherHelper->dispatch(\AppEvents::REPROCESS_START, $reprocessEvent);

        try {
            $preprocessedLink = new PreprocessedLink($url);
            $preprocessedLink->setFirstLink($link);
            $links = $this->processorService->reprocess(array($preprocessedLink));

            $reprocessEvent->setLinks($links);
            $this->dispatcherHelper->dispatch(\AppEvents::REPROCESS_FINISH, $reprocessEvent);

        } catch (\Exception $e) {
            $reprocessEvent->setError(sprintf('Error reprocessing link url "%s" with message "%s"', $url, $e->getMessage()));
            $this->dispatcherHelper->dispatch(\AppEvents::REPROCESS_ERROR, $reprocessEvent);
            $this->dispatchError($e, 'Reprocessing Links');

            return false;
        }

        return true;
    }
}
