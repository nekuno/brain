<?php

namespace Worker;

use Console\ApplicationAwareCommand;
use Event\ExceptionEvent;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Service\AMQPQueueService;
use Service\EventDispatcher;

abstract class LoggerAwareWorker implements LoggerAwareInterface, RabbitMQConsumerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    protected $queue;

    protected $queueManager;

    public function __construct(EventDispatcher $dispatcher, AMQPChannel $channel)
    {
        $this->dispatcher = $dispatcher;
        $this->channel = $channel;
        $this->queueManager = new AMQPQueueService();
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function consume()
    {
        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = $this->getTopic();
        $queueName = $this->getQueueName();

        $this->channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->queue_bind($queueName, $exchangeName, $topic);
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queueName, '', false, false, false, false, array($this, 'callbackWrapper'));

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function callbackWrapper(AMQPMessage $message)
    {
        $data = json_decode($message->body, true);
        $trigger = $this->queueManager->getTrigger($message);

        $this->callback($data, $trigger);

        /** @var AMQPChannel $channel */
        $channel = $message->delivery_info['channel'];
        $channel->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }

    abstract function callback(array $data, $trigger);

    protected function getTopic()
    {
        return $this->queueManager->buildPattern($this->queue);
    }

    protected function getQueueName()
    {
        return $this->queueManager->buildQueueName($this->queue);
    }

    protected function memory()
    {
        $this->logger->notice(sprintf('Current memory usage: %s', ApplicationAwareCommand::formatBytes(memory_get_usage(true))));
        $this->logger->notice(sprintf('Peak memory usage: %s', ApplicationAwareCommand::formatBytes(memory_get_peak_usage(true))));
    }

    //TODO: Move to dispatcher to make it available everywhere. Differentiate from dispatcher->dispatchError (sets neo4j source)
    protected function dispatchError(\Exception $e, $message)
    {
        $this->dispatcher->dispatch(\AppEvents::EXCEPTION_ERROR, new ExceptionEvent($e, $message));
    }

    protected function dispatchWarning(\Exception $e, $message)
    {
        $this->dispatcher->dispatch(\AppEvents::EXCEPTION_WARNING, new ExceptionEvent($e, $message));
    }
}