<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace EventListener;

use Event\LookUpSocialNetworkEvent;
use Model\Neo4j\GraphManager;
use Service\AMQPManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LookUpSocialNetworkSubscriber
 * @package EventListener
 */
class LookUpSocialNetworkSubscriber implements EventSubscriberInterface
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var AMQPManager
     */
    protected $amqpManager;

    /**
     * @param GraphManager $gm
     * @param AMQPManager $amqpManager
     */
    public function __construct(GraphManager $gm, AMQPManager $amqpManager)
    {

        $this->gm = $gm;
        $this->amqpManager = $amqpManager;
    }

    /**
     * { @inheritdoc }
     */
    public static function getSubscribedEvents()
    {

        return array(
            \AppEvents::LINKEDIN_SOCIAL_NETWORK_ADDED => array('onLinkedinSocialNetworkAdded'),
        );
    }

    public function onLinkedinSocialNetworkAdded(LookUpSocialNetworkEvent $event)
    {
        $message = array('id' => $event->getUserId(), 'profileUrl' => $event->getProfileUrl());

        $this->amqpManager->enqueueMessage($message, 'brain.social_network.linkedin');
    }
}
