<?php

namespace Worker;

use Console\ApplicationAwareCommand;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
abstract class LoggerAwareWorker implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {

        $this->logger = $logger;
    }

    protected function memory()
    {
        $this->logger->notice(sprintf('Current memory usage: %s', ApplicationAwareCommand::formatBytes(memory_get_usage(true))));
        $this->logger->notice(sprintf('Peak memory usage: %s', ApplicationAwareCommand::formatBytes(memory_get_peak_usage(true))));
    }

    protected function getTrigger(AMQPMessage $message)
    {
        $routingKey = $message->delivery_info['routing_key'];
        $parts = explode('.',$routingKey);

        return $parts[2];
    }
}