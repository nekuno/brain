<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 14/11/14
 * Time: 17:13
 */

namespace EventListener;

use AppEvents;
use Event\AnswerEvent;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserAnswerSubscriber
 * @package EventListener
 */
class UserAnswerSubscriber implements EventSubscriberInterface
{

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @param AMQPStreamConnection $connection
     */
    public function __construct(AMQPStreamConnection $connection)
    {

        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {

        return array(
            AppEvents::USER_ANSWER_QUESTION => array('onUserAnswer'),
        );
    }

    /**
     * @param AnswerEvent $event
     */
    public function onUserAnswer(AnswerEvent $event)
    {

        $data = array(
            'user_id' => $event->getUser(),
            'question_id' => $event->getQuestion(),
            'trigger' => 'question_answered'
        );

        $this->enqueueMatchingCalculation($data, 'brain.matching.question_answered');
    }

    /**
     * @param $data
     * @param $routingKey
     */
    private function enqueueMatchingCalculation($data, $routingKey)
    {

        $message = new AMQPMessage(json_encode($data, JSON_UNESCAPED_UNICODE));

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = 'brain.matching.*';
        $queueName = 'brain.matching';

        $channel = $this->connection->channel();
        $channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $exchangeName, $topic);
        $channel->basic_publish($message, $exchangeName, $routingKey);
    }
}