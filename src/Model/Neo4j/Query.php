<?php

namespace Model\Neo4j;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

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

        if ($this->logger instanceof LoggerInterface) {
            $now = microtime(true);
            try {
                $result = parent::getResultSet();
            } catch (\Exception $e) {
                $message = sprintf('Error executing Neo4j query: "%s"', $this->getExecutableQuery());
                $this->logger->error($message);
                throw $e;
            }
            $time = round(microtime(true) - $now, 3) * 1000;
            $message = sprintf('Executed Neo4j query (took %s ms): "%s"', $time, $this->getExecutableQuery());
            1000 <= $time ? $this->logger->warning($message) : $this->logger->debug($message);

            return $result;
        }

        return parent::getResultSet();
    }

    public function getExecutableQuery()
    {

        $query = $this->getQuery();

        foreach ($this->getParameters() as $parameter => $value) {

            $replace = null;

            switch (gettype($value)) {
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