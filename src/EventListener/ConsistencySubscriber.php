<?php

namespace EventListener;

use Event\ConsistencyEvent;
use Model\Popularity\PopularityManager;
use Service\Consistency\ConsistencyCheckerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsistencySubscriber implements EventSubscriberInterface
{
    protected $consistencyService;

    protected $popularityManager;

    public function __construct(ConsistencyCheckerService $consistencyService, PopularityManager $popularityManager)
    {
        $this->consistencyService = $consistencyService;
        $this->popularityManager = $popularityManager;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::CONSISTENCY_LINK => array('onLink')
        );
    }

    public function onLink(ConsistencyEvent $event)
    {
        $linkId = $event->getId();

        $this->popularityManager->deleteOneByLink($linkId);
        $this->popularityManager->updatePopularity($linkId);
    }
}