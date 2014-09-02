<?php


namespace Worker;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class MatchingCalculatorWorker
 * @package Worker
 */
class MatchingCalculatorWorker implements RabbitMQConsumerInterface
{

    /**
     * @var AMQPChannel
     */
    protected $channel;

    public function __construct(AMQPChannel $channel)
    {

        $this->channel = $channel;
    }

    /**
     * { @inheritdoc }
     */
    public function consume()
    {

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = 'brain.matching.*';
        $queueName = 'brain.matching';

        $this->channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->queue_bind($queueName, $exchangeName, $topic);

        $this->channel->basic_consume($queueName, '', false, false, false, false, array($this, 'callback'));

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * { @inheritdoc }
     */
    public function callback(AMQPMessage $message)
    {

        $data = json_decode($message->body, true);

        $eventType = $data['type'];

        switch ($eventType) {
            case 'process_finished':

                // TODO: handle this event
                break;
            case 'question_answered':

                // TODO: handle this event
                break;
            case 'content_vote':

                // TODO: handle this event
                break;
        }

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }

}
