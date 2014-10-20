<?php


namespace ApiConsumer\EventListener;

use ApiConsumer\Event\LinkEvent;
use ApiConsumer\Event\LinksEvent;
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
            \AppEvents::PROCESS_LINKS => array('onProcessLinks', 1),
            \AppEvents::PROCESS_LINK => array('onProcessLink', 1),
            \AppEvents::PROCESS_FINISH => array('onProcessFinish', 1),
        );
    }

    public function onProcessLinks(LinksEvent $event)
    {

        $data = $event->getData();
        $this->output->writeln(
            sprintf(
                'Processing links for user %s from resource owner %s with fetcher %s',
                $data['userId'],
                $data['resourceOwner'],
                $data['fetcher']
            )
        );
        $this->progress = new ProgressBar($this->output, $data['links']);
        $this->progress->start();
    }

    public function onProcessLink(LinkEvent $event)
    {

        $this->progress->advance();
    }

    public function onProcessFinish()
    {

        $this->progress->finish();
        $this->output->writeln('');
    }
}
