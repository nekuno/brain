<?php

namespace EventListener;

use Event\LookUpSocialNetworksEvent;
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
            \AppEvents::SOCIAL_NETWORKS_ADDED => array('onSocialNetworksAdded'),
        );
    }

    public function onSocialNetworksAdded(LookUpSocialNetworksEvent $event)
    {
        $message = array(
            'id' => $event->getUserId(),
            'socialNetworks' => $event->getSocialNetworks(),
        );

        $this->amqpManager->enqueueSocialNetwork($message);
    }
}
