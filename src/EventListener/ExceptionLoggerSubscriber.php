<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace EventListener;


use Event\ExceptionEvent;
use Model\Neo4j\Neo4jException;
use Model\Neo4j\Neo4jHandler;
use Monolog\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExceptionLoggerSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    protected $logger;
    
    protected $path;

    /**
     * ExceptionLoggerSubscriber constructor.
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::EXCEPTION_ERROR => array('onError'),
            \AppEvents::EXCEPTION_WARNING => array('onWarning'),
        );
    }

    public function onError(ExceptionEvent $event)
    {
        $exception = $event->getException();
//        $datetime = $event->getDatetime();
        $process = $event->getProcess();

        $context = array('source' => Neo4jHandler::NEO4J_SOURCE,
            'process' => $process,
        );

        if ($exception instanceof Neo4jException) {
            $context['query'] = $exception->getQuery();
        }

        $this->logger->addRecord(Logger::WARNING, $exception->getMessage(),$context);

    }

    public function onWarning(ExceptionEvent $event)
    {
        $exception = $event->getException();
//        $datetime = $event->getDatetime();
        $process = $event->getProcess();

        $context = array('source' => Neo4jHandler::NEO4J_SOURCE,
                        'process' => $process,
            );

        if ($exception instanceof Neo4jException) {
            $context['query'] = $exception->getQuery();
        }
        
        $this->logger->addRecord(Logger::ERROR, $exception->getMessage(),$context);
    }

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
}