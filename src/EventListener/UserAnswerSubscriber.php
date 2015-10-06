<?php

namespace EventListener;

use Event\AnswerEvent;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Service\EnqueueMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserAnswerSubscriber
 * @package EventListener
 */
class UserAnswerSubscriber implements EventSubscriberInterface
{

    /**
     * @var EnqueueMessage
     */
    protected $enqueueMessage;

    /**
     * @param EnqueueMessage $enqueueMessage
     */
    public function __construct(EnqueueMessage $enqueueMessage)
    {

        $this->enqueueMessage = $enqueueMessage;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {

        return array(
            \AppEvents::ANSWER_ADDED => array('onAnswerAdded'),
        );
    }

    /**
     * @param AnswerEvent $event
     */
    public function onAnswerAdded(AnswerEvent $event)
    {

        $data = array(
            'userId' => $event->getUser(),
            'question_id' => $event->getQuestion(),
            'trigger' => 'question_answered'
        );

        $this->enqueueMessage->enqueueMessage($data, 'brain.matching.question_answered');
    }
}