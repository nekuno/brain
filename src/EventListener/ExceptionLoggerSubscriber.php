<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace EventListener;


use Event\ExceptionEvent;
use Model\Neo4j\Neo4jException;
use Model\Neo4j\Query;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExceptionLoggerSubscriber implements EventSubscriberInterface
{

    protected $path;

    /**
     * ExceptionLoggerSubscriber constructor.
     */
    public function __construct()
    {
        $this->path = __DIR__ . '/../../var/logs/errors.log';
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
        $datetime = $event->getDatetime();
        $process = $event->getProcess();

        $string = '-------------------EXCEPTION-------------------' . PHP_EOL .
            'Time: ' . $datetime->format('Y-m-d H:i:s') . PHP_EOL;
        if ($process) {
            $string .= 'While: ' . $process . PHP_EOL;
        }
        $string .= 'Error message:' . $exception->getMessage() . PHP_EOL;
        if ($exception instanceof Neo4jException) {
            $string .= 'Query:' . $exception->getQuery();
        }

        $this->writeParagraph($string);

    }

    public function onWarning(ExceptionEvent $event)
    {
        $exception = $event->getException();
        $datetime = $event->getDatetime();
        $process = $event->getProcess();

        $string = '-------------------WARNING-------------------' . PHP_EOL .
            'Time: ' . $datetime->format('Y-m-d H:i:s') . PHP_EOL;
        if ($process) {
            $string .= 'While: ' . $process . PHP_EOL;
        }

        $string .= 'Error message:' . $exception->getMessage() . PHP_EOL;
        if ($exception instanceof Neo4jException) {
            /** @var Query $query */
            $query = $exception->getQuery();
            $string .= 'Query:' . $query->getExecutableQuery();
        }

        $this->writeParagraph($string);
    }

    private function writeParagraph($string)
    {
        $fp = fopen($this->path, "a+");
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, PHP_EOL);
            fwrite($fp, $string);
            fwrite($fp, PHP_EOL);
        } else {
            //couldn´t block the file
            return false;
        };
        return true;
    }
}