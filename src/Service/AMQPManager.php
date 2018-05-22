<?php

namespace Service;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPManager
{
    const MATCHING = 'matching';
    const MATCHING_PERIODIC = 'matchingPeriodic';
    const FETCHING = 'fetching';
    const PREDICTION = 'prediction';
    const SOCIAL_NETWORK = 'socialNetwork';
    const CHANNEL = 'channel';
    const REFETCHING = 'refetching';
    const LINKS_CHECK = 'linksCheck';
    const LINKS_REPROCESS = 'linksReprocess';

    protected $connection;

    /**
     * @var AMQPChannel[]
     */
    protected $publishingChannels = array();

    function __construct(AMQPStreamConnection $AMQPStreamConnection)
    {
        $this->connection = $AMQPStreamConnection;
        $this->queueManager = new AMQPQueueService();
    }

    public function enqueueFetching($messageData)
    {
        $this->enqueueMessage($messageData, self::FETCHING, 'links');
    }

    public function enqueueRefetching($messageData)
    {
        $this->enqueueMessage($messageData, self::REFETCHING, 'command');
    }

    public function enqueueLinkCheck($messageData)
    {
        $this->enqueueMessage($messageData, self::LINKS_CHECK, 'periodic');
    }

    public function enqueueLinkReprocess($messageData)
    {
        $this->enqueueMessage($messageData, self::LINKS_REPROCESS, 'periodic');
    }

    public function enqueueMatching($messageData, $trigger)
    {
        $this->enqueueMessage($messageData, self::MATCHING, $trigger);
    }

    public function enqueueMatchingPeriodic($messageData, $trigger)
    {
        $this->enqueueMessage($messageData, self::MATCHING_PERIODIC, $trigger);
    }

    public function enqueueChannel($messageData)
    {
        $this->enqueueMessage($messageData, self::CHANNEL, 'user_aggregator');
    }

    public function enqueueSocialNetwork($messageData)
    {
        $this->enqueueMessage($messageData, self::SOCIAL_NETWORK, 'added');
    }

    public function enqueuePrediction($messageData, $trigger)
    {
        $this->enqueueMessage($messageData, self::PREDICTION, $trigger);
    }

    private function enqueueMessage($messageData, $queue, $trigger)
    {
        $message = new AMQPMessage(json_encode($messageData, JSON_UNESCAPED_UNICODE));

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = $this->queueManager->buildPattern($queue);
        $queueName = $this->queueManager->buildQueueName($queue);
        $routingKey = $this->queueManager->buildRoutingKey($queue, $trigger);

        $channel = $this->getChannel($queueName);

        $channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $exchangeName, $topic);
        $channel->basic_publish($message, $exchangeName, $routingKey);
    }

    public function getMessagesCount($queue)
    {
        $queueName = $this->queueManager->buildQueueName($queue);
        $channel = $this->getChannel($queueName);

        return $this->queueManager->getEnqueuedCount($channel, $queueName);
    }

    public function getChannel($queueName)
    {
        if (isset($this->publishingChannels[$queueName])){
            $channel = $this->publishingChannels[$queueName];
        } else {
            $channel = $this->connection->channel();
            $this->publishingChannels[$queueName] = $channel;
        }

        return $channel;
    }

    public static function getValidConsumers()
    {
        return array(
            self::MATCHING,
            self::MATCHING_PERIODIC,
            self::FETCHING,
            self::PREDICTION,
            self::SOCIAL_NETWORK,
            self::CHANNEL,
            self::REFETCHING,
            self::LINKS_CHECK,
            self::LINKS_REPROCESS,
        );
    }
}