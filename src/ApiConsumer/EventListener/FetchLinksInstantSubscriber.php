<?php

namespace ApiConsumer\EventListener;

use Event\FetchEvent;
use Event\ProcessLinkEvent;
use Event\ProcessLinksEvent;
use GuzzleHttp\Exception\RequestException;
use Service\DeviceService;
use Service\InstantConnection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FetchLinksInstantSubscriber implements EventSubscriberInterface
{

    protected $instantConnection;

    /**
     * @var DeviceService
     */
    protected $deviceService;

    /**
     * @var integer
     */
    protected $current;

    /**
     * @var integer
     */
    protected $links;

    public function __construct(InstantConnection $instantConnection, DeviceService $deviceService)
    {
        $this->instantConnection = $instantConnection;
        $this->deviceService = $deviceService;
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
        $json = array('userId' => $event->getUser(), 'resource' => $event->getResourceOwner());
        $this->instantConnection->fetchStart($json);
    }

    public function onFetchFinish(FetchEvent $event)
    {
        $json = array('userId' => $event->getUser(), 'resource' => $event->getResourceOwner());
        $this->instantConnection->fetchFinish($json);
    }

    public function onProcessStart(ProcessLinksEvent $event)
    {
        $this->current = 0;
        $this->links = count($event->getLinks());
        $json = array('userId' => $event->getUser(), 'resource' => $event->getResourceOwner());
        $this->instantConnection->processStart($json);
    }

    public function onProcessLink(ProcessLinkEvent $event)
    {
        $percentage = floor($this->current++ / $this->links * 100);
        $json = array('userId' => $event->getUser(), 'resource' => $event->getResourceOwner(), 'percentage' => $percentage);
        $this->instantConnection->processLink($json);

    }

    public function onProcessFinish(ProcessLinksEvent $event)
    {
        $jsonProcess = array('userId' => $event->getUser(), 'resource' => $event->getResourceOwner());
        $this->instantConnection->processFinish($jsonProcess);

        $pushData = array(
            'resource' => $event->getResourceOwner(),
        );
        try {
            $this->deviceService->pushMessage($pushData, $event->getUser(), DeviceService::PROCESS_FINISH_CATEGORY);
        } catch (RequestException $e) {

        }
    }
}