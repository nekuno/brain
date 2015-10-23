<?php

namespace EventListener;

use Event\AnswerEvent;
use Service\AMQPManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserAnswerSubscriber
 * @package EventListener
 */
class UserAnswerSubscriber implements EventSubscriberInterface
{

    /**
     * @var AMQPManager
     */
    protected $amqpManager;

    /**
     * @param AMQPManager $amqpManager
     */
    public function __construct(AMQPManager $amqpManager)
    {

        $this->amqpManager = $amqpManager;
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

        $this->amqpManager->enqueueMessage($data, 'brain.matching.question_answered');
    }
}