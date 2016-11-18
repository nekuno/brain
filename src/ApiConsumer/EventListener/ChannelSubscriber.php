<?php


namespace ApiConsumer\EventListener;

use ApiConsumer\Event\ChannelEvent;
use Service\UserAggregator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ChannelSubscriber
 * @package ApiConsumer\EventListener
 */
class ChannelSubscriber implements EventSubscriberInterface
{

    protected $userAggregator;

    public function __construct( UserAggregator $userAggregator)
    {

        $this->userAggregator = $userAggregator;
    }

    public static function getSubscribedEvents()
    {

        return array(
            \AppEvents::CHANNEL_ADDED => array('onChannelAdded'),
        );
    }


    public function onChannelAdded(ChannelEvent $event)
    {

        $resourceOwner = $event->getResourceOwner();
        $username = $event->getUsername();


        if (!( $resourceOwner && $username)){
            throw new \Exception(sprintf('ERROR: Cant add channel with missing parameters. Resource = %s, Username = %s', $resourceOwner, $username));
        }

        $socialProfiles = $this->userAggregator->addUser($username, $resourceOwner);

        if ($socialProfiles){
            $this->userAggregator->enqueueChannel($socialProfiles, $username);
        }
    }

}
