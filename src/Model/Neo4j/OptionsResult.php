<?php

namespace Model\Neo4j;


class OptionsResult
{

    protected $total = 0;
    protected $updated = 0;
    protected $created = 0;

    public function incrementTotal()
    {
        $this->total++;
    }

    public function incrementUpdated()
    {
        $this->updated++;
    }

    public function incrementCreated()
    {
        $this->created++;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function getCreated()
    {
        return $this->created;
    }
}