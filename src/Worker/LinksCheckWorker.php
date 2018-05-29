<?php

namespace Worker;

use ApiConsumer\LinkProcessor\LinkProcessor;
use Event\CheckEvent;
use Model\Link\Link;
use Model\Link\LinkManager;
use Psr\Log\LoggerInterface;
use Service\AMQPManager;
use Service\EventDispatcherHelper;

class LinksCheckWorker extends LoggerAwareWorker implements RabbitMQConsumerInterface
{
    protected $queue = AMQPManager::LINKS_CHECK;

    protected $linkProcessor;

    /**
     * @var LinkManager
     */
    protected $linkModel;

    public function __construct(LoggerInterface $logger, EventDispatcherHelper $dispatcherHelper, LinkManager $linkModel, LinkProcessor $linkProcessor)
    {
        parent::__construct($logger, $dispatcherHelper);
        $this->linkProcessor = $linkProcessor;
        $this->linkModel = $linkModel;
    }

    /**
     * { @inheritdoc }
     */
    public function callback(array $data, $trigger)
    {
        $link = Link::buildFromArray($data['link']);
        $url = $link->getUrl();

        $checkEvent = new CheckEvent($url);
        $this->dispatcherHelper->dispatch(\AppEvents::CHECK_START, $checkEvent);

        if (!$this->linkProcessor->isLinkWorking($url)) {
            $this->linkModel->setProcessed($url, false);
            $checkEvent->setError(sprintf('Bad response status code for url "%s"', $url));
            $this->dispatcherHelper->dispatch(\AppEvents::CHECK_ERROR, $checkEvent);

            return;
        }

        $thumbnail = $link->getThumbnailLarge();
        if (!$this->linkProcessor->isLinkWorking($thumbnail)) {
            $this->linkModel->setProcessed($url, false);
            $checkEvent->setError(sprintf('Bad response status code for thumbnail "%s" for url "%s"', $thumbnail, $url));
            $this->dispatcherHelper->dispatch(\AppEvents::CHECK_ERROR, $checkEvent);

            return;
        }

        $this->dispatcherHelper->dispatch(\AppEvents::CHECK_SUCCESS, $checkEvent);
    }
}
