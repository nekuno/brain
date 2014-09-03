<?php


namespace Model\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity(repositoryClass="UserDataStatusRepository")
 * @Table(name="user_data_status")
 */
class UserDataStatus
{

    protected $id;

    /**
     * @Id()
     * @Column(name="user", type="integer")
     */
    protected $user;

    /**
     * @Id()
     * @Column(name="resourceOwner", type="string")
     */
    protected $resourceOwner;

    /**
     * @Column(name="connected", type="boolean", nullable=false, options={"default":false})
     */
    protected $connected;

    /**
     * @Column(name="fetched", type="boolean", nullable=false, options={"default":false})
     */
    protected $fetched;

    /**
     * @Column(name="processed", type="boolean", nullable=false, options={"default":false})
     */
    protected $processed;

    /**
     * @Column(name="update_at", type="datetime")
     */
    protected $updateAt;

    /**
     * Get user
     *
     * @return integer
     */
    public function getUser()
    {

        return $this->user;
    }

    /**
     * Set user
     *
     * @param integer $user
     * @return UserDataStatus
     */
    public function setUser($user)
    {

        $this->user = $user;

        return $this;
    }

    /**
     * Get resourceOwner
     *
     * @return string
     */
    public function getResourceOwner()
    {

        return $this->resourceOwner;
    }

    /**
     * Set resourceOwner
     *
     * @param string $resourceOwner
     * @return UserDataStatus
     */
    public function setResourceOwner($resourceOwner)
    {

        $this->resourceOwner = $resourceOwner;

        return $this;
    }

    /**
     * Get connected
     *
     * @return boolean
     */
    public function getConnected()
    {

        return $this->connected;
    }

    /**
     * Set connected
     *
     * @param boolean $connected
     * @return UserDataStatus
     */
    public function setConnected($connected)
    {

        $this->connected = $connected;

        return $this;
    }

    /**
     * Get fetched
     *
     * @return boolean
     */
    public function getFetched()
    {

        return $this->fetched;
    }

    /**
     * Set fetched
     *
     * @param boolean $fetched
     * @return UserDataStatus
     */
    public function setFetched($fetched)
    {

        $this->fetched = $fetched;

        return $this;
    }

    /**
     * Get processed
     *
     * @return boolean
     */
    public function getProcessed()
    {

        return $this->processed;
    }

    /**
     * Set processed
     *
     * @param boolean $processed
     * @return UserDataStatus
     */
    public function setProcessed($processed)
    {

        $this->processed = $processed;

        return $this;
    }

    /**
     * Get updateAt
     *
     * @return \DateTime
     */
    public function getUpdateAt()
    {

        return $this->updateAt;
    }

    public function setUpdateAt()
    {

        $this->updateAt = new \DateTime();
    }
}
