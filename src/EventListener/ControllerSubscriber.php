<?php

namespace EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array('onKernelResponse'),
            KernelEvents::CONTROLLER => array('onKernelController'),
        );
    }

    public function onKernelController(FilterControllerEvent $event)
    {
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
    }
}
