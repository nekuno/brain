<?php

namespace Model\Neo4j;


use Monolog\Handler\AbstractHandler;
use Monolog\Logger;

class Neo4jHandler extends AbstractHandler
{
    const NEO4J_SOURCE = 'neo4j';

    protected $path_error;
    protected $path_warning;

    /**
     * {@inheritDoc}
     */
    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->path_error = __DIR__ . '/../../../var/logs/errors.log';
        $this->path_warning = __DIR__ . '/../../../var/logs/warnings.log';
    }

    /**
     * Handles a record.
     *
     * All records may be passed to this method, and the handler should discard
     * those that it does not want to handle.
     *
     * The return value of this function controls the bubbling process of the handler stack.
     * Unless the bubbling is interrupted (by returning true), the Logger class will keep on
     * calling further handlers in the stack with a given log record.
     *
     * @param  array $record The record to handle
     * @return Boolean true means that this handler handled the record, and that bubbling is not permitted.
     *                        false means the record was either not processed or that this handler allows bubbling.
     */
    public function handle(array $record)
    {
        if (!(isset($record['context']['source']) && $record['context']['source'] == static::NEO4J_SOURCE)) {
            return false;
        }

        $datetime = isset($record['datetime'])? $record['datetime'] : new \DateTime();

        if (isset($record['level']) && $record['level'] == Logger::WARNING){
            $level = 'WARNING';
            $path = $this->path_warning;
        } else {
            $level = 'ERROR';
            $path = $this->path_error;
        }
        
        $string = '-------------------'.$level.'-------------------' . PHP_EOL .
            'Time: ' . $datetime->format('Y-m-d H:i:s') . PHP_EOL;
        if (isset($record['context']['process'])) {
            $string .= 'While: ' . $record['context']['process'] . PHP_EOL;
        }

        $string .= 'Error message:' . $record['message'] . PHP_EOL;
        
        if (isset($record['context']['time'])){
            $string .= PHP_EOL . 'Duration of the query:' . $record['context']['time'] .' ms';
        }
        
        if (isset($record['context']['query']) && $record['context']['query'] instanceof Query) {
            /** @var Query $query */
            $query = $record['context']['query'];
            $string .= PHP_EOL . 'Query:' . $query->getExecutableQuery();
        }

        $fp = fopen($path, "a+");
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, PHP_EOL);
            fwrite($fp, $string);
            fwrite($fp, PHP_EOL);
            fclose($fp);
        } else {
            //couldn´t block the file
            fclose($fp);
            return false;
        };
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isHandling(array $record)
    {
        return in_array($record['level'], array(Logger::ERROR, Logger::WARNING));
    }


}