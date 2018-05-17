<?php

namespace Model\Token\TokenStatus;

class TokenStatus
{
    protected $fetched = 0;

    protected $processed = 0;

    protected $resourceOwner;

    protected $updatedAt;

    /**
     * @return mixed
     */
    public function getFetched()
    {
        return $this->fetched;
    }

    /**
     * @param mixed $fetched
     */
    public function setFetched($fetched)
    {
        $this->fetched = $fetched;
    }

    /**
     * @return mixed
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    /**
     * @param mixed $processed
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;
    }

    /**
     * @return mixed
     */
    public function getResourceOwner()
    {
        return $this->resourceOwner;
    }

    /**
     * @param mixed $resourceOwner
     */
    public function setResourceOwner($resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

}