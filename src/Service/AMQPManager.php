<?php
/**
 * @author Roberto Martinez <yawmoght@gmail.com>
 */

namespace Service;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPManager
{
    const MATCHING = 'matching';
    const FETCHING = 'fetching';
    const PREDICTION = 'prediction';
    const SOCIAL_NETWORK = 'social_network';

    protected $connection;

    /**
     * @var AMQPChannel[]
     */
    protected $publishingChannels = array();

    function __construct(AMQPStreamConnection $AMQPStreamConnection)
    {
        $this->connection = $AMQPStreamConnection;
    }


    public function enqueueMessage($messageData, $routingKey)
    {
        $message = new AMQPMessage(json_encode($messageData, JSON_UNESCAPED_UNICODE));

        $publishData = $this->buildData($routingKey);

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = $publishData['topic'];
        $queueName = $publishData['queueName'];

        if (isset($this->publishingChannels[$queueName])){
            $channel = $this->publishingChannels[$queueName];
        } else {
            $channel = $this->connection->channel();
            $this->publishingChannels[$queueName] = $channel;
        }

        $channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $exchangeName, $topic);
        $channel->basic_publish($message, $exchangeName, $routingKey);
    }

    private function buildData($routingKey)
    {
        $parts = explode('.', $routingKey);
        $data = array();

        switch ($parts[1]){
            case $this::FETCHING:
                $data['topic'] = 'brain.fetching.*';
                $data['queueName'] = 'brain.fetching';
                break;
            case $this::MATCHING:
                $data['topic'] = 'brain.matching.*';
                $data['queueName'] = 'brain.matching';
                break;
            case $this::PREDICTION:
                $data['topic'] = 'brain.prediction.*';
                $data['queueName'] = 'brain.prediction';
                break;
            case $this::SOCIAL_NETWORK:
                $data['topic'] = 'brain.social_network.*';
                $data['queueName'] = 'brain.social_network';
                break;
            default:
                throw new \Exception('RabbitMQ queue not supported');
        }

        return $data;
    }


}