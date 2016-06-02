<?php

namespace Model\Neo4j;

use Model\Exception\ValidationException;
use Monolog\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Everyman\Neo4j\Exception;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class Query extends \Everyman\Neo4j\Cypher\Query implements LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @return \Everyman\Neo4j\Query\ResultSet
     * @throws \Exception
     */
    public function getResultSet()
    {

        $now = microtime(true);
        try {
            $result = parent::getResultSet();
        } catch (Exception $e) {
            $message = sprintf('Error executing Neo4j query: "%s"', $this->getExecutableQuery());
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->error($message);
            }
            if ($this->logger instanceof Logger){
                $this->logger->addRecord(Logger::ERROR, $message, array('source' => Neo4jHandler::NEO4J_SOURCE));
                $this->logger->addRecord(Logger::ERROR, $e->getTraceAsString(), array('source' => Neo4jHandler::NEO4J_SOURCE));
            }

            $query = str_replace(array("\n", "\r", '"'), array(' ', ' ', "'"), $this->getExecutableQuery());

            $data = $e->getData();

            if (isset($data['cause']['exception']) && $data['cause']['exception'] === 'UniqueConstraintViolationKernelException') {

                $errors = $data;
                if (isset($data['message']) && preg_match('/^.* property "(.*)".*/', $data['message'], $matches)) {
                    $errors = array($matches[1] => array($data['message']));
                }

                throw new ValidationException($errors);
            }

            throw new Neo4jException($e->getMessage(), $e->getCode(), $e->getHeaders(), $e->getData(), $query);
        }
        $time = round(microtime(true) - $now, 3) * 1000;
        $message = sprintf('Executed Neo4j query (took %s ms): "%s"', $time, $this->getExecutableQuery());
        if ($this->logger instanceof LoggerInterface) {
            1000 <= $time ? $this->logger->warning($message) : $this->logger->debug($message);
        }

        return $result;

    }

    public function getExecutableQuery()
    {

        $query = $this->getQuery();

        foreach ($this->getParameters() as $parameter => $value) {

            $replace = null;

            switch (gettype($value)) {
                case 'NULL':
                    $replace = 'NULL';
                    break;
                case 'boolean':
                    $replace = $value ? 'true' : 'false';
                    break;
                case 'integer':
                case 'double':
                    $replace = $value;
                    break;
                case 'string':
                    $replace = '"' . $value . '"';
                    break;
                case 'array':
                    $replace = '[' . (is_string(current($value)) ? '"' . implode('", "', $value) . '"' : implode(', ', $value)) . ']';
                    break;
            }

            if (!is_null($replace)) {
                $pattern = sprintf('/{\s?%s\s?}/', $parameter);
                $query = preg_replace($pattern, $replace, $query);
            }
        }

        return $query;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}