<?php

namespace Model\Popularity;


class Popularity
{
    // Link node or popularity node, given by $new
    protected $id;

    protected $popularity;

    protected $unpopularity;

    protected $timestamp;
    
    protected $amount;
    
    // Popularity node
    protected $new;

    /**
     * Popularity constructor.
     * @param $new
     */
    public function __construct($new = false)
    {
        $this->new = $new;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getPopularity()
    {
        return $this->popularity;
    }

    /**
     * @param mixed $popularity
     */
    public function setPopularity($popularity)
    {
        $this->popularity = $popularity;
    }

    /**
     * @return mixed
     */
    public function getUnpopularity()
    {
        return $this->unpopularity;
    }

    /**
     * @param mixed $unpopularity
     */
    public function setUnpopularity($unpopularity)
    {
        $this->unpopularity = $unpopularity;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param mixed $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = (integer)$amount;
    }

    /**
     * @return boolean
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * @param boolean $new
     */
    public function setNew($new)
    {
        $this->new = $new;
    }
    
    
    
}