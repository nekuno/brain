<?php

namespace ApiConsumer\LinkProcessor;

class SynonymousParameters
{

    protected $quantity = 3;

    protected $type;

    protected $query;

    protected $comparison;

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param mixed $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param mixed $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return mixed
     */
    public function getComparison()
    {
        return $this->comparison;
    }

    /**
     * @param mixed $comparison
     */
    public function setComparison($comparison)
    {
        $this->comparison = $comparison;
    }





}