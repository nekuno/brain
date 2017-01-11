<?php

namespace Worker;


use PhpAmqpLib\Message\AMQPMessage;

/**
 * Interface RabbitMQConsumerInterface
 * @package Worker
 */
interface RabbitMQConsumerInterface {

    /**
     * Starts to process queued messages
     */
    public function consume();

    /**
     * Process message
     *
     * @param AMQPMessage $message
     * @return mixed
     */
    public function callback(AMQPMessage $message);
}
