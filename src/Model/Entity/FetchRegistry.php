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
     * @Column(name="pointer", type="string", nullable=true)
     */
    protected $pointer;

    /**
     * @Column(name="pointer_field_name", type="string", nullable=true)
     */
    protected $pointerFieldName;

    /**
     * @Column(name="status", type="string", nullable=false)
     */
    protected $status = self::STATUS_SUCCESS;

    /**
     * Set id
     *
     * @param integer $id
     * @return FetchRegistry
     */
    public function setId($id)
    {

        $this->id = $id;

        return $this;
    }

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
     * Get fetchAt
     *
     * @return \DateTime
     */
    public function getFetchAt()
    {

        return $this->fetchAt;
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
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {

        return $this->userId;
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
     * Get resource
     *
     * @return string
     */
    public function getResource()
    {

        return $this->resource;
    }

    /**
     * Set pointer
     *
     * @param string $pointer
     * @return FetchRegistry
     */
    public function setPointer($pointer)
    {
        $this->pointer = $pointer;

        return $this;
    }

    /**
     * Get pointer
     *
     * @return string 
     */
    public function getPointer()
    {
        return $this->pointer;
    }

    /**
     * Set pointerFieldName
     *
     * @param string $pointerFieldName
     * @return FetchRegistry
     */
    public function setPointerFieldName($pointerFieldName)
    {
        $this->pointerFieldName = $pointerFieldName;

        return $this;
    }

    /**
     * Get pointerFieldName
     *
     * @return string 
     */
    public function getPointerFieldName()
    {
        return $this->pointerFieldName;
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
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }
}
