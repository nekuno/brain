<?php


namespace ApiConsumer\EventListener;

use Event\CheckEvent;
use Model\Link\LinkManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class CheckLinksSubscriber implements EventSubscriberInterface
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
            \AppEvents::CHECK_START => array('onCheckStart'),
            \AppEvents::CHECK_RESPONSE => array('onCheckResponse'),
            \AppEvents::CHECK_SUCCESS => array('onCheckSuccess'),
            \AppEvents::CHECK_ERROR => array('onCheckError'),
        );
    }

    public function onCheckStart(CheckEvent $event)
    {
        $this->linkModel->setLastChecked($event->getUrl());

        $this->writeMessageIfVerbose(sprintf('[%s] Checking link "%s"', date('Y-m-d H:i:s'), $event->getUrl()));
    }

    public function onCheckResponse(CheckEvent $event)
    {
        $this->writeMessageIfVerbose(sprintf('[%s] Received response "%s" from checking link "%s"', date('Y-m-d H:i:s'), $event->getResponse(), $event->getUrl()));
    }

    public function onCheckSuccess(CheckEvent $event)
    {
        $this->writeMessageIfVerbose(sprintf('[%s] Link "%s" successfully checked', date('Y-m-d H:i:s'), $event->getUrl()));
    }

    public function onCheckError(CheckEvent $event)
    {
        $this->output->writeln(sprintf('[%s] Link "%s" NOT successfully checked', date('Y-m-d H:i:s'), $event->getUrl()));
        $this->output->writeln(sprintf('Problem is: "%s"', $event->getError()));
    }

    private function writeMessageIfVerbose($message)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln($message);
        }
    }
}
