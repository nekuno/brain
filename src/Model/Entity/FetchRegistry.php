<?php

namespace Model\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity(repositoryClass="FetchRegistryRepository")
 * @Table(name="fetch_registries")
 * @HasLifecycleCallbacks()
 */
class FetchRegistry
{

    const STATUS_SUCCESS = 'success';

    const STATUS_ERROR = 'fail';

    /**
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    protected $id = null;

    /**
     * @Column(name="fetch_at", type="datetime")
     */
    protected $fetchAt;

    /**
     * @Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @Column(name="resource", type="string", nullable=false)
     */
    protected $resource;

    /**
     * @Column(name="last_item_id", type="string", nullable=true)
     */
    protected $lastItemId;

    /**
     * @Column(name="status", type="string", nullable=false)
     */
    protected $status = self::STATUS_SUCCESS;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {

        return $this->id;
    }

    /**
     * Get fetchAt
     *
     * @return \DateTime
     */
    public function getFetchAt()
    {

        return $this->fetchAt;
    }

    /**
     * Set fetchAt
     *
     * @PrePersist
     * @return FetchRegistry
     */
    public function setFetchAt()
    {

        if (null === $this->fetchAt) {
            $this->fetchAt = new \DateTime();
        }

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {

        return $this->userId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return FetchRegistry
     */
    public function setUserId($userId)
    {

        $this->userId = $userId;

        return $this;
    }

    /**
     * Get resource
     *
     * @return string
     */
    public function getResource()
    {

        return $this->resource;
    }

    /**
     * Set resource
     *
     * @param string $resource
     * @return FetchRegistry
     */
    public function setResource($resource)
    {

        $this->resource = $resource;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {

        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return FetchRegistry
     */
    public function setStatus($status)
    {

        $this->status = $status;

        return $this;
    }

    /**
     * Get lastItemId
     *
     * @return integer
     */
    public function getLastItemId()
    {

        return $this->lastItemId;
    }

    /**
     * Set lastItemId
     *
     * @param integer $lastItemId
     * @return FetchRegistry
     */
    public function setLastItemId($lastItemId)
    {

        $this->lastItemId = $lastItemId;

        return $this;
    }
}
