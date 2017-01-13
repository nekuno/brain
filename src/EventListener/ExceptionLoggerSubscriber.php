<?php

namespace EventListener;


use ApiConsumer\Exception\CannotProcessException;
use Event\ExceptionEvent;
use Model\Exception\ValidationException;
use Model\Neo4j\Neo4jException;
use Model\Neo4j\Neo4jHandler;
use Monolog\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExceptionLoggerSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    protected $logger;

    protected $consistency_file;
    protected $urlUnprocessed_file;

    /**
     * ExceptionLoggerSubscriber constructor.
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->consistency_file = __DIR__ . '/../../var/logs/consistency_errors.log';
        $this->urlUnprocessed_file = __DIR__ . '../../var/logs/url_unprocessed.log';
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::EXCEPTION_ERROR => array('onError'),
            \AppEvents::EXCEPTION_WARNING => array('onWarning'),
            \AppEvents::CONSISTENCY_ERROR => array('onConsistencyError'),
            \AppEvents::CONSISTENCY_START => array('onConsistencyStart'),
            \AppEvents::CONSISTENCY_END => array('onConsistencyEnd'),
            \AppEvents::URL_UNPROCESSED => array('onUrlUnprocessed'),
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

    public function onConsistencyError(ExceptionEvent $event)
    {
        /** @var ValidationException $exception */
        $exception = $event->getException();
        $errors = $exception->getErrors();

        $lines = array();
        foreach ($errors as $field => $error) {
            foreach ($error as $name => $message) {
                $lines[] = sprintf('%s error related to %s: %s', $field, $name, $message);
            }
        }

        $this->writeToFile($this->consistency_file, $lines);
    }

    public function onConsistencyStart()
    {
        $now = (new \DateTime('now'))->format('Y-m-d');

        $lines = array(
            '---------------------------------',
            'Starting consistency check on '. $now,
            '---------------------------------',
        );

        $this->writeToFile($this->consistency_file, $lines);
    }

    public function onConsistencyEnd()
    {
        $now = (new \DateTime('now'))->format('Y-m-d');

        $lines = array(
            '---------------------------------',
            'Ending consistency check on '. $now,
            '---------------------------------',
        );

        $this->writeToFile($this->consistency_file, $lines);
    }

    public function onUrlUnprocessed(ExceptionEvent $e)
    {
        /** @var CannotProcessException $exception */
        $exception = $e->getException();

        $message = $exception->getMessage();
        $process = $e->getProcess();
        $now = (new \DateTime('now'))->format('Y-m-d');

        $lines = array(
            $message,
            sprintf('On $s while $s', $now, $process),
        );

        $this->writeToFile($this->urlUnprocessed_file, $lines);
    }

    private function writeToFile($file, array $lines)
    {
        $fp = fopen($file, "a+");
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, PHP_EOL);
            foreach ($lines as $line) {
                fwrite($fp, $line);
            }
            fwrite($fp, PHP_EOL);
        } else {
            //couldn´t block the file
        };
        fclose($fp);
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
}