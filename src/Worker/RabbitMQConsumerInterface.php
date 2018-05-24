<?php

namespace Worker;


use PhpAmqpLib\Message\AMQPMessage;


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
    public function callbackWrapper(AMQPMessage $message);
}
