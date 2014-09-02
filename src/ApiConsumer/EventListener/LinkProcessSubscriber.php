<?php


namespace ApiConsumer\EventListener;

use ApiConsumer\Event\MatchingEvent;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LinkProcessSubscriber
 * @package ApiConsumer\EventListener
 */
class LinkProcessSubscriber implements EventSubscriberInterface
{

    protected $connection;

    /**
     * @param AMQPConnection $connection
     */
    public function __construct(AMQPConnection $connection)
    {

        $this->connection = $connection;
    }

    /**
     * { @inheritdoc }
     */
    public static function getSubscribedEvents()
    {

        return array(
            \AppEvents::PROCESS_FINISH => array('onProcessFinish', 1),
        );
    }

    /**
     * @param MatchingEvent $event
     */
    public function onProcessFinish(MatchingEvent $event)
    {

        $data = $event->getData();
        $message = new AMQPMessage(json_encode($data, JSON_UNESCAPED_UNICODE));

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $routingKey = 'brain.matching.process';
        $topic = 'brain.matching.*';
        $queueName = 'brain.matching';

        $channel = $this->connection->channel();
        $channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $exchangeName, $topic);
        $channel->basic_publish($message, $exchangeName, $routingKey);

    }

}
