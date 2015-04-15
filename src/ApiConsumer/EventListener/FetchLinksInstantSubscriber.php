<?php


namespace ApiConsumer\EventListener;

use Event\FetchEvent;
use Event\ProcessLinkEvent;
use Event\ProcessLinksEvent;
use GuzzleHttp\ClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FetchLinksInstantSubscriber implements EventSubscriberInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var integer
     */
    protected $current;

    /**
     * @var integer
     */
    protected $links;

    public function __construct(ClientInterface $client, $host)
    {

        $this->client = $client;
        $this->host = $host;
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
        $json = array('resource' => $event->getResourceOwner());
        $this->client->post($this->host . 'api/fetch/start', array('json' => $json));
    }

    public function onFetchFinish(FetchEvent $event)
    {
        $json = array('resource' => $event->getResourceOwner());
        $this->client->post($this->host . 'api/fetch/finish', array('json' => $json));
    }

    public function onProcessStart(ProcessLinksEvent $event)
    {
        $this->current = 0;
        $this->links = count($event->getLinks());
        $json = array('resource' => $event->getResourceOwner());
        $this->client->post($this->host . 'api/process/start', array('json' => $json));
    }

    public function onProcessLink(ProcessLinkEvent $event)
    {
        $percentage = floor($this->current++ / $this->links * 100);
        $json = array('resource' => $event->getResourceOwner(), 'percentage' => $percentage);
        $this->client->post($this->host . 'api/process/link', array('json' => $json));
    }

    public function onProcessFinish(ProcessLinksEvent $event)
    {
        $json = array('resource' => $event->getResourceOwner());
        $this->client->post($this->host . 'api/process/finish', array('json' => $json));
    }
}