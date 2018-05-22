<?php


namespace ApiConsumer\EventListener;

use Event\ReprocessEvent;
use Model\Link\LinkManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class ReprocessLinksSubscriber implements EventSubscriberInterface
{

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var LinkManager
     */
    protected $linkModel;

    public function __construct(OutputInterface $output, LinkManager $linkModel)
    {
        $this->output = $output;
        $this->linkModel = $linkModel;
    }

    public static function getSubscribedEvents()
    {

        return array(
            \AppEvents::REPROCESS_START => array('onReprocessStart'),
            \AppEvents::REPROCESS_FINISH => array('onReprocessFinish'),
            \AppEvents::REPROCESS_ERROR => array('onReprocessError'),
        );
    }

    public function onReprocessStart(ReprocessEvent $event)
    {
        $this->linkModel->setLastReprocessed($event->getUrl());

        $this->writeMessageIfVerbose(sprintf('[%s] Reprocessing link "%s"', date('Y-m-d H:i:s'), $event->getUrl()));
    }

    public function onReprocessFinish(ReprocessEvent $event)
    {
        $this->writeMessageIfVerbose(sprintf('[%s] Reprocessed links from url "%s"', date('Y-m-d H:i:s'), $event->getUrl()));

        $linksProcessedCount = 0;
        foreach ($event->getLinks() as $link) {
            $link->getProcessed() ? $linksProcessedCount++ : null;
        }

        if ($linksProcessedCount > 0) {
            $this->linkModel->initializeReprocessed($event->getUrl());
        } else {
            $this->linkModel->increaseReprocessed($event->getUrl());
        }

        $this->writeMessageIfVerbose(sprintf('%s links processed found', $linksProcessedCount));
    }

    public function onReprocessError(ReprocessEvent $event)
    {
        $this->linkModel->increaseReprocessed($event->getUrl());

        $this->output->writeln(sprintf('[%s] Error reprocessing link "%s"', date('Y-m-d H:i:s'), $event->getUrl()));
        $this->output->writeln(sprintf('Problem is: "%s"', $event->getError()));
    }

    private function writeMessageIfVerbose($message)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln($message);
        }
    }
}
