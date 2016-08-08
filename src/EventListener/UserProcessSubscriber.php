<?php

namespace EventListener;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Event\UserProcessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserProcessSubscriber
 * @package EventListener
 */
class UserProcessSubscriber implements EventSubscriberInterface
{
	/**
	 * @var Client
	 */
	protected $client;

	public function __construct(Client $client)
	{
		$this->client = $client;
	}

    /**
     * { @inheritdoc }
     */
    public static function getSubscribedEvents()
    {

        return array(
            \AppEvents::USER_PROCESS_STARTED => array('onUserProcessStart'),
            \AppEvents::USER_PROCESS_FINISHED => array('onUserProcessFinish'),
        );
    }

    public function onUserProcessStart(UserProcessEvent $event)
    {
	    $json = array('userId' => $event->getUserId(), 'process' => $event->getProcess());
	    try {
		    $this->client->post('api/user/process/start', array('json' => $json));
	    } catch (RequestException $e) {

	    }
    }

	public function onUserProcessFinish(UserProcessEvent $event)
	{
		$json = array('userId' => $event->getUserId(), 'process' => $event->getProcess());
		try {
			$this->client->post('api/user/process/finish', array('json' => $json));
		} catch (RequestException $e) {

		}
	}
}
