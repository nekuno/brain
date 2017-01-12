<?php


namespace ApiConsumer\EventListener;

use Event\FetchEvent;
use Event\ProcessLinkEvent;
use Event\ProcessLinksEvent;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class FetchLinksSubscriber implements EventSubscriberInterface
{

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ProgressBar
     */
    protected $progress;

    public function __construct(OutputInterface $output)
    {

        $this->output = $output;
    }

    public static function getSubscribedEvents()
    {

        return array(
            \AppEvents::FETCH_START => array('onFetchStart'),
            \AppEvents::FETCH_FINISH => array('onFetchFinish'),
            \AppEvents::PROCESS_START => array('onProcessStart'),
            \AppEvents::PROCESS_LINK => array('onProcessLink'),
            \AppEvents::PROCESS_FINISH => array('onProcessFinish'),
        );
    }

    public function onFetchStart(FetchEvent $event)
    {
        $this->output->writeln(sprintf('[%s] Fetching links for user "%d" from resource owner "%s"', date('Y-m-d H:i:s'), $event->getUser(), $event->getResourceOwner()));
    }

    public function onFetchFinish(FetchEvent $event)
    {
        $this->output->writeln(sprintf('[%s] Fetched links for user "%d" from resource owner "%s"', date('Y-m-d H:i:s'), $event->getUser(), $event->getResourceOwner()));
    }

    public function onProcessStart(ProcessLinksEvent $event)
    {

        $this->output->writeln(sprintf('[%s] Processing "%d" links for user "%d" from resource owner "%s"', date('Y-m-d H:i:s'), count($event->getLinks()), $event->getUser(), $event->getResourceOwner()));

        $this->progress = new ProgressBar($this->output, count($event->getLinks()));

        if (OutputInterface::VERBOSITY_NORMAL < $this->output->getVerbosity()) {
            $this->progress->start();
        }
    }

    public function onProcessLink(ProcessLinkEvent $event)
    {

        if (OutputInterface::VERBOSITY_NORMAL < $this->output->getVerbosity()) {
            $this->progress->advance();
        }
        if (OutputInterface::VERBOSITY_VERBOSE < $this->output->getVerbosity()) {
            $link = $event->getLink();
            $url = $link->getUrl();
            $timestamp = $link->getFirstLink()->getCreated() ?: time()*1000 ;
            $this->output->writeln(sprintf(' url: "%s" at timestamp: %s', $url, $timestamp));
        }
    }

    public function onProcessFinish(ProcessLinksEvent $event)
    {

        $this->output->writeln(sprintf('[%s] Processed "%d" links for user "%d" from resource owner "%s"', date('Y-m-d H:i:s'), count($event->getLinks()), $event->getUser(), $event->getResourceOwner()));

        if (OutputInterface::VERBOSITY_NORMAL < $this->output->getVerbosity()) {
            $this->progress->finish();
            $this->output->writeln('');
        }
    }
}
