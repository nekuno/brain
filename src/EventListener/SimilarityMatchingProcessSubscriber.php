<?php

namespace EventListener;

use Event\MatchingProcessEvent;
use Event\MatchingProcessStepEvent;
use Event\SimilarityProcessEvent;
use Event\SimilarityProcessStepEvent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SimilarityMatchingProcessSubscriber
 * @package EventListener
 */
class SimilarityMatchingProcessSubscriber implements EventSubscriberInterface
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
            \AppEvents::SIMILARITY_PROCESS_START => array('onSimilarityProcessStart'),
            \AppEvents::SIMILARITY_PROCESS_STEP => array('onSimilarityProcessStep'),
            \AppEvents::SIMILARITY_PROCESS_FINISH => array('onSimilarityProcessFinish'),
            \AppEvents::MATCHING_PROCESS_START => array('onMatchingProcessStart'),
            \AppEvents::MATCHING_PROCESS_STEP => array('onMatchingProcessStep'),
            \AppEvents::MATCHING_PROCESS_FINISH => array('onMatchingProcessFinish'),
        );
    }

    public function onSimilarityProcessStart(SimilarityProcessEvent $event)
    {
        $json = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId());
        try {
            $this->client->post('api/similarity/start', array('json' => $json));
        } catch (RequestException $e) {

        }
    }

    public function onSimilarityProcessStep(SimilarityProcessStepEvent $event)
    {
        $json = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId(), 'percentage' => $event->getPercentage());
        try {
            $this->client->post('api/similarity/step', array('json' => $json));
        } catch (RequestException $e) {

        }
    }

    public function onSimilarityProcessFinish(SimilarityProcessEvent $event)
    {
        $json = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId());
        try {
            $this->client->post('api/similarity/finish', array('json' => $json));
        } catch (RequestException $e) {

        }
    }

    public function onMatchingProcessStart(MatchingProcessEvent $event)
    {
        $json = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId());
        try {
            $this->client->post('api/matching/start', array('json' => $json));
        } catch (RequestException $e) {

        }
    }

    public function onMatchingProcessStep(MatchingProcessStepEvent $event)
    {
        $json = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId(), 'percentage' => $event->getPercentage());
        try {
            $this->client->post('api/matching/step', array('json' => $json));
        } catch (RequestException $e) {

        }
    }

    public function onMatchingProcessFinish(MatchingProcessEvent $event)
    {
        $json = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId());
        try {
            $this->client->post('api/matching/finish', array('json' => $json));
        } catch (RequestException $e) {

        }
    }
}
