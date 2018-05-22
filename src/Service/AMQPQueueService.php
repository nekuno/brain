<?php

namespace Service;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPQueueService
{
    public function buildRoutingKey($queue, $trigger)
    {
        return sprintf('brain.%s.%s', $queue, $trigger);
    }

    public function buildPattern($queue)
    {
        return sprintf('brain.%s.*', $queue);
    }

    public function buildQueueName($queue)
    {
        return ucfirst($queue);
    }

    public function getTrigger(AMQPMessage $message)
    {
        $routingKey = $message->delivery_info['routing_key'];
        $parts = explode('.',$routingKey);

        return $parts[2];
    }

    public function getEnqueuedCount(AMQPChannel $channel, $queue)
    {
        $response = $channel->queue_declare($queue, true, true, false, false);

        return is_int($response[1]) ? $response[1] : 0;
    }
}