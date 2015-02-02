<?php

namespace Model\Neo4j;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class CypherPart
{
    protected $cypherPartName;
    protected $cypherPart;

    public function __construct($cypherPartName, $cypherPart)
    {
        $this->cypherPartName = mb_strtoupper($cypherPartName);
        $this->cypherPart = $cypherPart;
    }

    public function getCypherPartName()
    {
        return $this->cypherPartName;
    }

    public function getCypherPart()
    {
        return $this->cypherPart;
    }

}