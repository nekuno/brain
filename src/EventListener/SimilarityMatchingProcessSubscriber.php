<?php

namespace EventListener;

use Event\AffinityProcessEvent;
use Event\AffinityProcessStepEvent;
use Event\MatchingProcessEvent;
use Event\MatchingProcessStepEvent;
use Event\SimilarityProcessEvent;
use Event\SimilarityProcessStepEvent;
use GuzzleHttp\Exception\RequestException;
use Service\InstantConnection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SimilarityMatchingProcessSubscriber
 * @package EventListener
 */
class SimilarityMatchingProcessSubscriber implements EventSubscriberInterface
{
    /**
     * @var InstantConnection
     */
    protected $instantConnection;

    public function __construct(InstantConnection $instantConnection)
    {
        $this->instantConnection = $instantConnection;
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
            \AppEvents::AFFINITY_PROCESS_START => array('onAffinityProcessStart'),
            \AppEvents::AFFINITY_PROCESS_STEP => array('onAffinityProcessStep'),
            \AppEvents::AFFINITY_PROCESS_FINISH => array('onAffinityProcessFinish'),
        );
    }

    public function onSimilarityProcessStart(SimilarityProcessEvent $event)
    {
        $data = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId());
        try {
            $this->instantConnection->similarityStart($data);
        } catch (RequestException $e) {
        }
    }

    public function onSimilarityProcessStep(SimilarityProcessStepEvent $event)
    {
        $data = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId(), 'percentage' => $event->getPercentage());
        try {
            $this->instantConnection->similarityStep($data);
        } catch (RequestException $e) {
        }
    }

    public function onSimilarityProcessFinish(SimilarityProcessEvent $event)
    {
        $data = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId());
        try {
            $this->instantConnection->similarityFinish($data);
        } catch (RequestException $e) {
        }
    }

    public function onMatchingProcessStart(MatchingProcessEvent $event)
    {
        $data = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId());
        try {
            $this->instantConnection->matchingStart($data);
        } catch (RequestException $e) {
        }
    }

    public function onMatchingProcessStep(MatchingProcessStepEvent $event)
    {
        $data = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId(), 'percentage' => $event->getPercentage());
        try {
            $this->instantConnection->matchingStep($data);
        } catch (RequestException $e) {
        }
    }

    public function onMatchingProcessFinish(MatchingProcessEvent $event)
    {
        $data = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId());
        try {
            $this->instantConnection->matchingFinish($data);
        } catch (RequestException $e) {
        }
    }

    public function onAffinityProcessStart(AffinityProcessEvent $event)
    {
        $data = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId());
        try {
            $this->instantConnection->affinityStart($data);
        } catch (RequestException $e) {
        }
    }

    public function onAffinityProcessStep(AffinityProcessStepEvent $event)
    {
        $data = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId(), 'percentage' => $event->getPercentage());
        try {
            $this->instantConnection->affinityStep($data);
        } catch (RequestException $e) {

        }
    }

    public function onAffinityProcessFinish(AffinityProcessEvent $event)
    {
        $data = array('userId' => $event->getUserId(), 'processId' => $event->getProcessId());
        try {
            $this->instantConnection->affinityFinish($data);
        } catch (RequestException $e) {

        }
    }
}
