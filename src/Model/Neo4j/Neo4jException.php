<?php

namespace Model\Neo4j;

use Everyman\Neo4j\Exception;


class Neo4jException extends Exception
{
    protected $query;

    public function __construct($message, $code = 0, $headers = array(), $data = array(), $query = '')
    {

        $this->query = $query;
        parent::__construct($message, $code, $headers, $data);
    }

    /**
     * Return query
     * @return string Query
     */
    public function getQuery()
    {
        return $this->query;
    }

}