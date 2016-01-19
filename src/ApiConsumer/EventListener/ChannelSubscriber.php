<?php


namespace ApiConsumer\EventListener;

use ApiConsumer\Event\ChannelEvent;
use Service\UserAggregator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ChannelSubscriber
 * @package ApiConsumer\EventListener
 */
class ChannelSubscriber implements EventSubscriberInterface
{

    /**
     * @var OutputInterface
     */
    protected $output;

    protected $userAggregator;

    public function __construct(OutputInterface $output, UserAggregator $userAggregator)
    {

        $this->output = $output;
        $this->userAggregator = $userAggregator;
    }

    public static function getSubscribedEvents()
    {

        return array(
            \AppEvents::ADD_CHANNEL => array('onAddChannel'),
        );
    }


    public function onAddChannel(ChannelEvent $event)
    {

        $resourceOwner = $event->getResourceOwner();
        $username = $event->getChannelUrl();

            $this->output->writeln(sprintf(' Adding channel with URL and username $s for resource %s', $username, $resourceOwner));

        if (!( $resourceOwner && $username)){
            $this->output->writeln(' ERROR: Cant add channel with missing parameters');
        }

        //add ghost user with that url

        $socialProfiles = $this->userAggregator->addUser($username, $resourceOwner);

        //enqueue got fetching with that username

        if (OutputInterface::VERBOSITY_VERBOSE < $this->output->getVerbosity()) {
            $link = $event->getLink();
            $url = $link['url'];
            $timestamp = $link['timestamp'];
            $this->output->writeln(sprintf(' url: "%s" at timestamp: %s', $url, $timestamp));
        }
    }

}
