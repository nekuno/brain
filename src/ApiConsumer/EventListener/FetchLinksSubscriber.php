<?php


namespace ApiConsumer\EventListener;

use Event\FetchingEvent;
use Event\ProcessLinkEvent;
use Event\ProcessLinksEvent;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LinkProcessSubscriber
 * @package ApiConsumer\EventListener
 */
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
            \AppEvents::FETCHING_START => array('onFetchingStart'),
            \AppEvents::FETCHING_FINISH => array('onFetchingFinish'),
            \AppEvents::PROCESS_START => array('onProcessStart'),
            \AppEvents::PROCESS_LINK => array('onProcessLink'),
            \AppEvents::PROCESS_FINISH => array('onProcessFinish'),
        );
    }

    public function onFetchingStart(FetchingEvent $event)
    {
        $this->output->writeln(sprintf('[%s] Fetching links for user "%d" with fetcher "%s" from resource owner "%s"', date('Y-m-d H:i:s'), $event->getUser(), $event->getFetcher(), $event->getResourceOwner()));
    }

    public function onFetchingFinish(FetchingEvent $event)
    {
        $this->output->writeln(sprintf('[%s] Fetched links for user "%d" with fetcher "%s" from resource owner "%s"', date('Y-m-d H:i:s'), $event->getUser(), $event->getFetcher(), $event->getResourceOwner()));
    }

    public function onProcessStart(ProcessLinksEvent $event)
    {

        $this->progress = new ProgressBar($this->output, count($event->getLinks()));

        if (OutputInterface::VERBOSITY_NORMAL < $this->output->getVerbosity()) {
            $this->output->writeln(sprintf('[%s] Processing "%d" links for user "%d" with fetcher "%s" from resource owner "%s"', date('Y-m-d H:i:s'), count($event->getLinks()), $event->getUser(), $event->getFetcher(), $event->getResourceOwner()));
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
            $url = $link['url'];
            $this->output->writeln(sprintf(' "%s"', $url));
        }
    }

    public function onProcessFinish(ProcessLinksEvent $event)
    {

        if (OutputInterface::VERBOSITY_NORMAL < $this->output->getVerbosity()) {
            $this->progress->finish();
            $this->output->writeln('');
        }
    }
}
